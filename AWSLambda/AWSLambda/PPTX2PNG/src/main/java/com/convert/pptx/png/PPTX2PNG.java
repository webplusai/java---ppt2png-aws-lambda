package com.convert.pptx.png;

/**
 * Created by RBN on 16-09-29.
 */

import 	java.io.InputStream;
import 	java.io.IOException;
import 	java.io.ByteArrayInputStream;
import 	java.io.ByteArrayOutputStream;

import 	java.awt.Dimension;
import 	java.awt.Graphics2D;
import 	java.awt.RenderingHints;
import 	java.awt.image.BufferedImage;

import 	java.net.URLDecoder;

import 	java.util.Set;
import 	java.util.List;
import 	java.util.TreeSet;
import 	java.util.regex.Matcher;
import 	java.util.regex.Pattern;

import 	javax.imageio.ImageIO;

import com.amazonaws.auth.AWSCredentials;
import com.amazonaws.services.s3.model.*;
import 	org.apache.poi.sl.draw.DrawFactory;

import 	org.apache.poi.sl.usermodel.Slide;
import 	org.apache.poi.sl.usermodel.SlideShow;
import 	org.apache.poi.sl.usermodel.SlideShowFactory;

import 	com.amazonaws.services.s3.event.S3EventNotification.S3EventNotificationRecord;

import 	com.amazonaws.services.s3.AmazonS3;
import 	com.amazonaws.services.s3.AmazonS3Client;

import 	com.amazonaws.services.lambda.runtime.Context;
import 	com.amazonaws.services.lambda.runtime.RequestHandler;
import 	com.amazonaws.services.lambda.runtime.events.S3Event;

public class PPTX2PNG implements RequestHandler<S3Event, String> {

    AWSCredentials credentials;

    float 			scale 				= 	3f;
    String 			format 				= 	"png";
    String 			slidenumStr 		= 	"-1";

    String 			JPG_TYPE 			= 	(String) "jpg";
    String 			JPG_MIME 			= 	(String) "image/jpeg";
    String 			PNG_TYPE 			= 	(String) "png";
    String 			PNG_MIME 			= 	(String) "image/png";
    String			PPT_TYPE			=	(String) "pptx";


    public void setCredentials(AWSCredentials credentials) {
        this.credentials = credentials;
    }

    static void usage(String error) {

        String msg =
                "Usage: PPTX2PNG [options] <ppt or pptx file>\n" +
                        (error == null ? "" : ("Error: " + error + "\n")) +
                        "Options:\n" +
                        "    -scale <float>   	scale factor\n" +
                        "    -slide <integer>	1-based index of a slide to render\n" +
                        "    -format <type>   	png,gif,jpg (,null for testing)" +
                        "    -outdir <dir>    	output directory, defaults to origin of the ppt/pptx file" +
                        "    -quiet           	do not write to console (for normal processing)";

        System.out.println(msg);
    }


    @Override
    public String handleRequest(S3Event s3event, Context context) {

        context.getLogger().log("Input: " + s3event);

        try {

            S3EventNotificationRecord record = s3event.getRecords().get(0);


            String 		srcKey 		= 	record.getS3().getObject().getKey().replace('+', ' ');
            String 		dstKey 		= 	srcKey + "_pg";
            String 		srcBucket 	= 	record.getS3().getBucket().getName();
            String 		dstBucket 	= 	srcBucket + "converted";

            srcKey = URLDecoder.decode(srcKey, "UTF-8");

            // Sanity check: validate that source and destination are different buckets.
            if (srcBucket.equals(dstBucket)) {
                System.out.println("Destination bucket must not match source bucket.");
                return "Destination";
            }

            // Infer the image type.
            Matcher matcher = Pattern.compile(".*\\.([^\\.]*)").matcher(srcKey);
            if (!matcher.matches()) {
                System.out.println("Unable to infer image type for key " + srcKey);
                return "Infer Image";
            }

            String imageType = matcher.group(1);
            if (!(PPT_TYPE.equals(imageType))) {
                System.out.println("Skipping non-pptx file " + srcKey);
                return "Non-pptx";
            }

            // Download the image from S3 into a stream

            AmazonS3 		s3Client;
            if(credentials !=null){
                s3Client = new AmazonS3Client(credentials);
            }else
            s3Client = new AmazonS3Client();

            S3Object 		s3Object 		= s3Client.getObject(new GetObjectRequest(srcBucket, srcKey));
            InputStream 	objectData 		= s3Object.getObjectContent();

            if (format == null || !format.matches("^(png|gif|jpg|null)$")) {
                usage("Invalid format given");
                return "Invalid Format";
            }

            if (scale < 0) {
                usage("Invalid scale given");
                return "Invalid Scale";
            }

            SlideShow<?,?> ss = SlideShowFactory.create(objectData);//, null, true);

            try {

                List<? extends Slide<?, ?>> 	slides 		= 	ss.getSlides();
                Set<Integer> 					slidenum 	= 	slideIndexes(slides.size(), slidenumStr);

                if (slidenum.isEmpty()) {
                    usage("slidenum must be either -1 (for all) or within range: [1.." + slides.size() + "] for " + objectData);
                    return "SlideNum";
                }

                Dimension 		pgsize 		= 	ss.getPageSize();
                int 			width 		= 	(int) (pgsize.width  * scale);
                int 			height 		= 	(int) (pgsize.height * scale);

                for (Integer slideNo : slidenum) {
                    Slide<?, ?> 	slide 		= 	slides.get(slideNo);
                    BufferedImage 	img 		= 	new BufferedImage(width, height, BufferedImage.TYPE_INT_ARGB);
                    Graphics2D 		graphics 	= 	img.createGraphics();

                    DrawFactory.getInstance(graphics).fixFonts(graphics);

                    // default rendering options
                    graphics.setRenderingHint(RenderingHints.KEY_RENDERING, RenderingHints.VALUE_RENDER_QUALITY);
                    graphics.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                    graphics.setRenderingHint(RenderingHints.KEY_INTERPOLATION, RenderingHints.VALUE_INTERPOLATION_BICUBIC);
                    graphics.setRenderingHint(RenderingHints.KEY_FRACTIONALMETRICS, RenderingHints.VALUE_FRACTIONALMETRICS_ON);

                    graphics.scale(scale, scale);

                    // draw stuff
                    slide.draw(graphics);

                    // save the result
                    if (!"null".equals(format)) {

                        // Re-encode image to target format
                        ByteArrayOutputStream os = new ByteArrayOutputStream();

                        ImageIO.write(img, format, os);

                        InputStream 		is 		= new ByteArrayInputStream(os.toByteArray());
                        ObjectMetadata 		meta 	= new ObjectMetadata();

                        meta.setContentLength(os.size());

                        if (JPG_TYPE.equals(format)) {
                            meta.setContentType(JPG_MIME);
                        }
                        if (PNG_TYPE.equals(format)) {
                            meta.setContentType(PNG_MIME);
                        }

                        String destName = srcKey + "/" + dstKey + slideNo.toString() + ".png";


                        // Uploading to S3 destination bucket
                        System.out.println("Writing to: " + dstBucket + "/" +destName);
                        PutObjectResult res = s3Client.putObject(dstBucket, destName, is, meta);
                        System.out.println(res.getETag());
                        System.out.println("Successfully resized " + srcBucket + "/" + srcKey + " and uploaded to " +  dstBucket + "/"+ destName);
                    }

                    graphics.dispose();
                    img.flush();
                }
            } finally {
                ss.close();
            }
        }
        catch (IOException e) {
            throw new RuntimeException(e);
        }

        return "OK";
    }

    private static Set<Integer> slideIndexes(final int slideCount, String range) {

        Set<Integer> slideIdx = new TreeSet<Integer>();

        if ("-1".equals(range)) {
            for (int i = 0; i < slideCount; i++) {
                slideIdx.add(i);
            }
        } else {
            for (String subrange : range.split(",")) {
                String idx[] = subrange.split("-");
                switch (idx.length)
                {
                    default:
                    case 0:
                        break;
                    case 1:
                    {
                        int subidx = Integer.parseInt(idx[0]);

                        if (subrange.contains("-")) {
                            int 	startIdx 	= 	subrange.startsWith("-") ? 0 : subidx;
                            int 	endIdx 		= 	subrange.endsWith("-") ? slideCount : Math.min(subidx,slideCount);

                            for (int i = Math.max(startIdx,1); i < endIdx; i++) {
                                slideIdx.add(i-1);
                            }
                        } else {
                            slideIdx.add(Math.max(subidx,1)-1);
                        }
                        break;
                    }
                    case 2:
                    {
                        int 	startIdx 	= 	Math.min(Integer.parseInt(idx[0]), slideCount);
                        int 	endIdx 		= 	Math.min(Integer.parseInt(idx[1]), slideCount);

                        for (int i = Math.max(startIdx,1); i < endIdx; i++) {
                            slideIdx.add(i - 1);
                        }
                        break;
                    }
                }
            }
        }
        return slideIdx;
    }
}
