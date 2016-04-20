	<?php

include_once 'db.inc.php';

class Comments 
{
	// our database connection
	public $db;
	
	// Array for containing comments
	public $comments;
	
	// Upon class instantiation, open a database connection
	public function __construct()
	{
		// Open database connection and store it
		$this->db = new PDO(DB_INFO, DB_USER, DB_PASS);
	}
	
	// Display a form for users to enter new comments with
	public function showCommentForm($blog_id) {

		$errors = array(
			1 => '<p class="error">Something wen wrong while saving your comment. Please try again!</p>',
			2 => '<p class="error">Please provide a valid email address!</p>',
			3 => '<p class="error">Please answer the question correctly!'
		);
		if(isset($_SESSION['error']))
		{
			$error = $errors[$_SESSION['error']];
		}
		else
		{
			$error = NULL;
		}

		// Check if session data is available
		if(isset($_SESSION['c_name']))
		{
			$n = $_SESSION['c_name'];
		}
		else
		{
			$n = NULL;
		}
		if(isset($_SESSION['c_email']))
		{
			$e = $_SESSION['c_email'];
		}
		else
		{
			$e = NULL;
		}
		if(isset($_SESSION['c_comment']))
		{
			$c = $_SESSION['c_comment'];
		}
		else
		{
			$c = NULL;
		}

		// Generate a challenge question
		$challenge = $this->generateChallenge();

		return <<<FORM
<form action="/simple_blog/inc/update.inc.php"
	method="post" id="comment-form">
	<fieldset>
		<legend>Post a Comment</legend>$error
		<label>Name
			<input type="text" name="name" maxlength="75" value="$n" />
		</label>
		<label>Email
			<input type="text" name="email" maxlength="150" value="$e" />
		</label>
		<label>Comment
			<textarea rows="10" cols="45" name="comment" value="$c"></textarea>
		</label>$challenge
		<input type="hidden" name="blog_id" value="$blog_id" />
		<input type="submit" name="submit" value="Post Comment" />
		<input type="submit" name="submit" value="Cancel" />
	</fieldset>
</form>
FORM;
	}

	// save comments to the database
	public function saveComment($p)
	{

		// Save the comment information in a session
		$_SESSION['c_name'] = htmlentities($p['name'], ENT_QUOTES);
		$_SESSION['c_email'] = htmlentities($p['email'], ENT_QUOTES);
		$_SESSION['c_comment'] = htmlentities($p['cmnt'], ENT_QUOTES);

		// Make sure the email address is valid first
		if($this->validateEmail($p['email'])===FALSE)
		{
			$_SESSION['error'] = 2;
			return;
		}

		// Check if challenge is answered correctly
		if(!$this->verifyResponse($p['s_q'], $p['s_1'], $p['s_2']))
		{
			$_SESSION['error'] = 3;
			return;
		}

		// Sanititze the data and store inside variables
		$blog_id = htmlentities(strip_tags($p['blog_id']), ENT_QUOTES);
		$name = htmlentities(strip_tags($p['name']), ENT_QUOTES);
		$email = htmlentities(strip_tags($p['email']), ENT_QUOTES);
		$comment = htmlentities(strip_tags($p['comment']), ENT_QUOTES);
		// Keep formatting of comments and remove extra whitespace
		$comment = nl2br(trim($comment));
		
		// Generate and prepare SQL command
		$sql = 'INSERT INTO comments (blog_id, name, email, comment)
				VALUES (?,?,?,?)';
		if($stmt = $this->db->prepare($sql))
		{
			// Execute the command, free used memory, and return true
			$stmt->execute(array($blog_id, $name, $email, $comment));
			$stmt->closeCursor();

			// Destroy comment info to empty the form
			unset($_SESSION['c_name'], $_SESSION['c_email'], $_SESSION['c_comment'], $_SESSION['error']);
			return TRUE;
		}
		else
		{
			// If something goes wrong, return false
			$_SESSION['error'] = 1;
			return;
		}
	}

	private function validateEmail($email)
	{
		// Matches valid email addresses
		$p = '/^[\w-]+(\.[\w-]+)*@[a-z0-9-]+'
			.'(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i';

		// if match is found return true else false
		return (preg_match($p, $email)) ? TRUE : FALSE;
	}

	// Load all comments for a blog entry into memory
	public function retrieveComments($blog_id)
	{
		$sql = "SELECT id, name, email, comment, date
				FROM comments
				WHERE blog_id=?
				ORDER BY date DESC";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($blog_id));
		
		// loop throught returned rows
		while($comment = $stmt->fetch())
		{
			// Store in memory for later use
			$this->comments[] = $comment;
		}
		
		// Set up a defualt response if no comments exist
		if(empty($this->comments))
		{
			$this->comments[] = array(
					'id' => NULL,
					'name' => NULL,
					'email' => NULL,
					'comment' => "There are no comments on this entry.",
					'date' => NULL
			);
		}
	}

	// Generates HTML markup for displaying comments
	public function showComments($blog_id)
	{
		// Initialize the variable in case no comments exist
		$display = NULL;
		
		// Load the comments for the entry
		$this->retrieveComments($blog_id);
		
		// Loop through the stored comments related to $blog_id
		foreach($this->comments as $c)
		{
			// Prevent empty fields if no comments exist
			if(!empty($c['date']) && !empty($c['name']))
			{
				// Out puts similar to: July 8, 2009 at 4:39PM
				$format = "F j, Y \a\\t g:iA";
				
				// Convert $c['date'] to a timestamp, then format
				$date = date($format, strtotime($c['date']));
				
				// Generate byline for the comment
				$byline = "<span><strong>$c[name]</strong>
						[Posted on $date]</span>";
				
				if(isset($_SESSION['loggedin'])
					&& $_SESSION['loggedin'] == 1)
				{
					// Generate delete link for comment display
					$admin = "<a href=\"/simple_blog/inc/update.inc.php"
							. "?action=comment_delete&id=$c[id]\""
							. "class=\"admin\">delete</a>";
				}
				else
				{
					$admin = NULL;
				}
			}
			else 
			{
				// If no comments exist, set $byline & $admin to NULL
				$byline = NULL;
				$admin = NULL;
			}
			
			// Assemble the pieces into formatted comment
			$display .= "
	<p class=\"comment\">$byline$c[comment]$admin</p>";
		}
		
		// Return all the formatted comments as a string
		return $display;
	}

	// Confirmation for deleting a comment
	public function confirmDelete($id)
	{
		if(isset($_SERVER['HTTP_REFERER']))
		{
			$url = $_SERVER['HTTP_REFERER'];
		}
		// Otherwise use the default view
		else 
		{
			$url = "../";
		}
		
		return <<<FORM
<html>
<head>
<title>Please Confirm Your Decision</title>
<link rel="stylesheet" type="text/css"
	href="/simple_blog/css/default.css" />
</head>
<body>
<form action="/simple_blog/inc/update.inc.php" method="post">
	<fieldset>
		<legend>Are you sure?</legend>
			<p>
				Are you sure you want to delete this comment?
			</p>
		<input type="hidden" name="id" value="$id" />
		<input type="hidden" name="action" value="comment_delete" />
		<input type="hidden" name="url" value="$url" />
		<input type="submit" name="confirm" value="Yes" />
		<input type="submit" name="confirm" value="No" />
	</fieldset>
</form>
</body>
</html>
FORM;
	}

	public function	deleteComment($id)
	{
		$sql = "DELETE FROM comments
				WHERE id=?
				LIMIT 1";
		if($stmt = $this->db->prepare($sql))
		{
			// Execute the command, free used mem, and return true
			$stmt->execute(array($id));
			$stmt->closeCursor();
			return TRUE;
		}
		else 
		{
			// If something goes wrong return false
			return FALSE;
		}
	}

	private function generateChallenge()
	{
		// Store two random numbers in an array
		$numbers = array(mt_rand(1,4), mt_rand(1,4));

		// Store the correct answer in a session
		$_SESSION['challenge'] = $numbers[0] + $numbers[1];

		// Convert the numbers to ASCII codes
		$converted = array_map('ord', $numbers);

		// Genreate a math question as HTML markup
		return "
		<label>&#87;&#104;&#97;&#116;&#32;&#105;&#115;&#32;
			&#$converted[0];&#32;&#43;&#32;&#$converted[1];&#63;
			<input type=\"text\" name=\"s_q\" />
		</label>";
	}

	private function verifyResponse($resp)
	{
		// Take value from session data
		$val = $_SESSION['challenge'];
		unset($_SESSION['challenge']);

		// Returns TRUE if correct, else FALSE
		return $resp==$val;
	}
}

?>