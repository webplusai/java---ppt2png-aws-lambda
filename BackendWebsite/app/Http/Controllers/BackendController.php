<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Screenshot;
use App\Comment;

class BackendController extends Controller
{
    public function uploadScreenshot() {

    	// Decode base 64 string to png data.
		$img = $_POST['image'];
		$img = str_replace('data:image/png;base64,', '', $img);
		$img = str_replace(' ', '+', $img);
		$data = base64_decode($img);

		// Generate random filename.
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $charactersLength = strlen($characters);
	    $randomString = '';

	    for ($i = 0; $i < 10; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }

	    $filePath = "screenshots/" . $randomString . "-" . time();

	    // Save screenshot to database.
	    $screenshot = new Screenshot();
	    $screenshot->image_name = substr($filePath, 12);
	    $screenshot->save();

		// Write PNG file.
		file_put_contents($filePath . ".png", $data);
		echo json_encode( array("filePath" => $filePath, "screenshot_id" => $screenshot->id ) );
    }

    public function viewScreenshot($id) {
    	$screenshot = Screenshot::where('image_name', '=', $id)->get();
    	$comments = Comment::where("screenshot_id", '=', $screenshot[0]->id)->get();
    	return view('screenshot')->with(array('screenshot' => $id . '.png', 'comments' => $comments));
    }

    public function addComment() {
    	
    	$comment = new Comment();

    	$comment->screenshot_id = $_GET['screenshot_id'];
    	$comment->x_pos = $_GET['x_pos'];
    	$comment->y_pos = $_GET['y_pos'];
    	$comment->comment = str_replace("\n", "!~!~!~!~", str_replace("'", ".,.,.,.,", str_replace('"', "-=-=-=-=", $_GET['comment'])));

    	$comment->save();

    	echo "Success";
    }
}
