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
                <div id="path">Uploading image. Please wait...</div>
            </div>
            <input type="hidden" name="_token" value="{{csrf_token()}}">
        </div>

        <form id="frmImg"  role="form" action="{{url('/')}}/upload/screenshot" method="post">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <img id="image" onmousedown="moveBubble(event)">
        </form>

        <div id="bubble" style="left: 200px; top: 400px; display: none;">
            <div id="header"> </div>
            <div id="content">
                <textarea id="comment_text" onkeydown ="onKeyDown(event)"> </textarea>
            </div>
            <div id="footer" onmousedown="onMouseDown()" onmouseup="onMouseUp()"> </div>
        </div>

        <img id="comment" src="{{url('/')}}/assets/img/comment.png" style="position: absolute; left: 195px; top: 390px; z-index: 1; display: none;">

        <script type="text/javascript">

            var btnPressed = 0;
            var screenshot_id;
            var x_pos;
            var y_pos;

            var comment_count = 1;

            var image_uploaded = false;

            document.addEventListener("csEvent", function(event) {

                $.ajax({
                    url: "{{url('/')}}/upload/screenshot",
                    type: "POST",
                    data: {image: $("#image").attr("src") , _token: $("input[name='_token']").val()},
                    success: function(data){
                        var result = JSON.parse(data);
                        $("#path").text("{{url('/')}}/" + result.filePath);
                        screenshot_id = result.screenshot_id;

                        jQuery("#image").attr("src", event.detail.image);

                        image_uploaded = true;
                    }
                });
            });

            function moveBubble(event) {

                if (image_uploaded == true) {
                    $("#comment").css("left", (event.clientX - 10) + "px");
                    $("#comment").css("top", (event.clientY - 10) + "px");
                    $("#comment").css("display", "block");

                    $("#bubble").css("left", (event.clientX - 5) + "px");
                    $("#bubble").css("top", event.clientY + "px");
                    $("#bubble").css("display", "block");

                    x_pos = event.clientX;
                    y_pos = event.clientY;
                }
            }

            function showBubble(index) {
                $("div[no='" + index + "']").css('display', 'block');
            }

            function hideBubble(index) {
                $("div[no='" + index + "']").css('display', 'none');
            }

            function addComment(clientX, clientY) {
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
                bubble += "<div id='content'> <textarea id='comment_text' onkeydown='onKeyDown(event)'>" + $("#comment_text").val() + "</textarea> </div>";
                bubble += "<div id='footer' onmousedown='onMouseDown()' onmouseup='onMouseUp()'> </div>";
                bubble += "</div>";

                $("body").append(bubble);

                $("#comment").css("display", "none");
                $("#bubble").css("display", "none");

                $.ajax({
                    url: "{{url('/')}}/add/comment",
                    type: "GET",
                    data: {screenshot_id : screenshot_id, y_pos : y_pos - $("#path").height(), x_pos : x_pos, comment : $("#comment_text").val()},
                    success: function(data){
                        
                    }
                });

                comment_count ++;
            }

            function onKeyDown(event) {
                var text = $("#comment_text").val();

                if (event.keyCode == 8 && text.length <= 1)
                {
                    $("#footer").css('background-image', "url({{url('/')}}/assets/img/bubble_footer.png)");
                    $("#footer").css('height', '6px');
                }
                else
                {
                    $("#footer").css('background-image', "url({{url('/')}}/assets/img/bubble_post.png)");
                    $("#footer").css('height', '40px');    
                }
            }

            function onMouseDown() {
                $("#footer").css('background-image', "url({{url('/')}}/assets/img/bubble_post_pressed.png)");
                btnPressed = 1;
            }

            function onMouseUp() {
                $("#footer").css('background-image', "url({{url('/')}}/assets/img/bubble_post.png)");

                if (btnPressed == 1)
                {
                    addComment(x_pos, y_pos);
                }
                btnPressed = 0;
            }
        </script>
    </body>
</html>