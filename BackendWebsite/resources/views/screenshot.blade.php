<!DOCTYPE html>
<html>
    <head>
        <title>Create Comment</title>
    </head>

    <script src="{{url('/')}}/assets/js/jquery-2.2.4.min.js"> </script>
    <link href="{{url('/')}}/assets/css/bubble.css" rel="stylesheet">

    <body>
        <div class="container">
            <div class="content">
                <div id="path"></div>
            </div>
            <input type="hidden" name="_token" value="{{csrf_token()}}">
        </div>

        <form id="frmImg"  role="form" action="{{url('/')}}/upload/screenshot" method="post">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <img id="image">
        </form>

        <script type="text/javascript">
            var imageName = "<?php echo $screenshot; ?>";
            var comments = [];
            var comment_count = 1;

            <?php foreach($comments as $comment) { ?>
                var comment = JSON.parse('<?php echo json_encode($comment); ?>');
                comment.comment = comment.comment.replace(/-=-=-=-=/g, '"');
                comment.comment = comment.comment.replace(/.,.,.,.,/g, "'");
                comment.comment = comment.comment.replace(/!~!~!~!~/g, "\n");
                comments.push(comment);
            <?php } ?>

            $(document).ready(function () {
                $("#image").attr("src", "{{url('/')}}/screenshots/" + imageName);

                for (i = 0; i < comments.length; i++) {
                    addComment(comments[i].x_pos, comments[i].y_pos, comments[i].comment);
                }
            });

            function addComment(clientX, clientY, comment_text) {
                var comment = "";
                
                comment += "<img id='comment' src='";
                comment += "{{url('/')}}";
                comment += "/assets/img/comment.png' style='position: absolute; left: ";
                comment += (clientX - 10) + "px; top: ";
                comment += (clientY - 10) + "px; z-index: 1;' no='" + comment_count + "'";
                comment += "onmouseover='showBubble(" + comment_count + ")'" + "onmouseout='hideBubble(" + comment_count + ")'>";
                
                $("body").append(comment);

                var bubble = "";

                bubble += "<div id='bubble' style='left:" + (clientX - 5) + "px; " + "top:" + clientY + "px; display: none; ' no='" + comment_count + "'>";
                bubble += "<div id='header'> </div>";
                bubble += "<div id='content'> <textarea id='comment_text' onkeydown='onKeyDown(event)'>" + comment_text + "</textarea> </div>";
                bubble += "<div id='footer' onmousedown='onMouseDown()' onmouseup='onMouseUp()'> </div>";
                bubble += "</div>";

                $("body").append(bubble);

                comment_count ++;
            }

            function showBubble(index) {
                $("div[no='" + index + "']").css('display', 'block');
            }

            function hideBubble(index) {
                $("div[no='" + index + "']").css('display', 'none');
            }
            
        </script>
    </body>
</html>