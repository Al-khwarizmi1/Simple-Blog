<?php 

session_start();

// If the user is logged in, we can continue
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==1):

/*
 * Add neccessary files
 */

include_once 'inc/functions.inc.php';
include_once 'inc/db.inc.php';

// open new database connection

$db = new PDO(DB_INFO, DB_USER, DB_PASS);

if(isset($_GET['page'])){
	$page = htmlentities(strip_tags($_GET['page']));
}
else {
	$page = 'blog';
}


// Check to see if the user wants to delete the entry
if(isset($_POST['action']) && $_POST['action'] == 'delete'){
	if ($_POST['submit'] == 'Yes'){
		$url = htmlentities(strip_tags($_POST['url']));
		if(deleteEntry($db, $url)){
			header("Location: /simple_blog/");
			exit;
		} else {
			exit("Error deleting the entry!");
		}
	} else {
		header("Location: /simple_blog/blog/$url");
		exit;
	}
}

if (isset($_GET['url'])){
	// do basic sanitization of url variable
	$url = htmlentities(strip_tags($_GET['url']));
	
	// Check if the entry should be deleted
	if($page == 'delete'){
		$confirm = confirmDelete($db, $url);	
	}
	
	// set the legend of the form
	$legend = "Edit this Entry";
	
	//load the entry to be edited
	$e = retrieveEntries($db, $page, $url);

	// save each entry field as individual variables
	$id = $e['id'];
	$title = $e['title'];
	$entry = $e['entry'];

} else {

	// Check if we're creating a new user
	if($page == 'createuser')
	{
		$create = createUserForm();
	}

	// Set the legend 
	$legend = "New Entry Submission";
	
	// Set variables to NULL if not editing
	$id = NULL;
	$title = NULL;
	$entry = NULL;
}

?>
<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
	<meta http-equiv="Content-type" content="text/html;charset=utf-8" />
	<link rel="stylesheet" href="/simple_blog/css/default.css" type="text/css" />
	<title> Simple Blog </title>
</head>

<body>
	<h1>Simple Blog Application</h1>
	
	<?php 
	
	if($page == 'delete'):{
		echo $confirm;
	} 
	elseif ($page == 'createuser'):
	{
		echo $create;
	}
	else:
	
	?>
	
	<form method="post" 
	action="/simple_blog/inc/update.inc.php"
	enctype="multipart/form-data">
	<fieldset>
		<legend><?php echo $legend ?></legend>
		<label>Title
			<input type="text" name="title" maxlength="150"
			value="<?php echo htmlentities($title) ?>"/>
		</label>
		<label>Image
			<input type="file" name="image" />
		</label>
		<label>Entry
			<textarea name="entry" cols="45" rows="10">
			<?php echo sanitizeData($entry)?>
			</textarea>
		</label>
		<input type="hidden" name="id" value="<?php echo $id ?>" />
		<input type="hidden" name="page" value="<?php echo $page ?>" />
		<input type="submit" name="submit" value="Save Entry" />
		<input type="submit" name="submit" value="Cancel" />
	</fieldset>
	</form>
	<?php endif;?>
</body>

</html>

<?php 

/* If we get here, the user isn't logged in
* This will display a form to ask them to login
*/
else:

?>
<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
	<meta http-equiv="Content-type" content="text/html;charset=utf-8" />
	<link rel="stylesheet" href="/simple_blog/css/default.css" type="text/css" />
	<title> Welcome, please login </title>
</head>

<body>
	
	<form method="post"
		action="/simple_blog/inc/update.inc.php"
		enctype="multipart/form-data">
		<fieldset>
			<legend>Please Log In to Proceed</legend>
			<label>Username
				<input type="text" name="username" maxlength="75" />
			</label>
			<label>Password
				<input type="password" name="password" maxlength="150" />
			</label>
			<input type="hidden" name="action" value="login" />
			<input type="submit" name="submit" value="Log In" />
		</fieldset>
		
	</form>
</body>
</html>

<?php endif; ?>