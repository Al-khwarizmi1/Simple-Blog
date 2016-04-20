<?php 

session_start();

/*
 * Include the necessary files
 */
include_once 'inc/functions.inc.php';
include_once 'inc/db.inc.php';

//open databse connection
$db = new PDO(DB_INFO, DB_USER, DB_PASS);

/*
 * Figure out what page is being requested (defualt=blog)
 * Perform basic sanitization on the variable as well
 */

if(isset($_GET['page'])){
	$page = htmlentities(strip_tags($_GET['page']));
}
else {
	$page = 'blog';
}
//determine if an entry URL was passed in the URL
$url = (isset($_GET['url'])) ? $_GET['url']: NULL;

$e = retrieveEntries($db, $page, $url);

// Get the fulldisp flag and remove it from the array
$fulldisp = array_pop($e);

//sanitize the entry data
$e = sanitizeData($e);
?>

<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<meta http-equiv="Content-type" content="text/html;charset=utf-8" />
	<link rel="stylesheet" href="/simple_blog/css/default.css" type="text/css" />
	<link rel="alternate" type="application/rss+xml"
	title="My Simple Blog - RSS 2.0"
	href="/simple_blog/feeds/rss.php" />
	<title> Simple Blog	</title>
</head>

<body>
	<h1> Simple Blog Application</h1>
	<ul id="menu">
		<li><a href="/simple_blog/blog/">Blog</a></li>
		<li><a href="/simple_blog/about/">About the Author</a></li>
	</ul>
	
<?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']==1): ?>
	<p id="control_panel">
		Welcome, Admin!
		<a href="/simple_blog/inc/update.inc.php?action=logout">Log out</a>		
	</p>
<?php endif; ?>

	<div id="entries">
	
	<?php 
		
	//Format the entries from the database
		
	//if the full display flag is set, show the entry
	if ($fulldisp == 1){
		//Get the URL if one wasn't passed
		$url = (isset($url)) ? $url: $e['url'];

		if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']== 1)
		{
			// Build the admin links
			$admin = adminlinks($page, $url);
		}
		else
		{
			$admin = array('edit'=>NULL, 'delete'=>NULL);
		}
		
		// Format the image if one exists
		$img = formatImage($e['image'], $e['title']);
		
		if($page=='blog')
		{
			// Load the comment object
			include_once 'inc/comments.inc.php';
			$comments = new Comments();
			$comment_disp = $comments->showComments($e['id']);
			$comment_form = $comments->showCommentForm($e['id']);

			// Generate post to twitter link
			$twitter = postToTwitter($e['title']);
		}
		else
		{
			$comment_form = NULL;
			$twitter = NULL;
		}
		
	?>
	<h2><?php echo $e['title']?></h2>
	<p><?php echo $img, $e['entry']?></p>
	<p>
		<?php echo $admin['edit']?>
		<?php if ($page=='blog') echo $admin['delete'] ?>
	</p>
	<?php  if($page == 'blog'):?>
	<p class="backlink">
		<a href="<?php echo $twitter ?>">Post to Twitter</a><br />
		<a href="./">Back to Latest Entries</a>
	</p>
	<h3> Comments for this Entry</h3>
	<?php echo $comment_disp, $comment_form; endif; ?>
	<?php } //End if statement 
	else {
		//loop through each entry
		foreach($e as $entry){
			?>
			<p>
				<a href="/simple_blog/<?php echo $entry['page']?>/<?php echo $entry['url']?>">
				<?php echo $entry['title']?>
				</a>
			</p>
		<?php } //End foreach loop
	} //End the else statement
	?>
		<?php //if($page == 'blog'):?>
		<p class="backlink">
		<?php

		if($page == 'blog'
			&& isset($_SESSION['loggedin'])
			&& $_SESSION['loggedin'] == 1):
		?>
			<a href="/simple_blog/admin/<?php echo $page ?>">Post New Entry</a>
		<?php endif; ?>
		</p>
		<?php // endif ?>
		
		<p>
			<a href="/simple_blog/feeds/rss.php">
				Subscribe via RSS!
			</a>
		</p>
		
	</div>
	
</body>

</html>