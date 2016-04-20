<?php

//get entries from database
function retrieveEntries($db, $page, $url=NULL) {
	
	//if an entry URL was given, load the associated entry
	if(isset($url)){
		//load specified entry
		$sql = "SELECT id, page, title, image, entry, created
				FROM entries
				WHERE url=?
				LIMIT 1";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($url));
		
		//save the returned entry array
		$e = $stmt->fetch();
		
		//set fulldisp flag for a sinlge entry
		$fulldisp = 1;
	}
	
	//if no entry URL was supplied, load all entry titles for the page
	
	else{
		//Load entry titles
		$sql = "SELECT id, page, title, image, entry, url, created
				FROM entries
				WHERE page=?
				ORDER BY created DESC";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($page));
		
		$e = NULL; //Declare variable to avoid errors
		//loop through returned results and store as an array
		while ($row = $stmt->fetch()){
			if($page=='blog'){
				$e[] = $row;
				$fulldisp = 0;
			}
			else {
				$e = $row;
				$fulldisp = 1;
			}
		}
//		ORGINAL CODE FOR QUERY GRABBING, REPLACED BY ABOVE WHILE STMT
// 		foreach ($db->query($sql) as $row){
// 			$e[] = array(
// 				'id' => $row['id'],
// 				'title' => $row['title']
// 			);
// 		}
		
		// set the fulldisp flag for multiple entries
		// $fulldisp = 0; <-- This was commented out after inserting
		// if/else statement in while loop above
		// this removes $fulldisp default so you can view authors section
		// without the preview page
		
		/* If no entires were returned, display a default
		 * message and set the fulldisp flag to display a
		 * single entry
		 */
		
		if(!is_array($e)){
			$fulldisp = 1;
			$e = array(
				'title' => 'No Entries Yet',
				'entry' => "<a href='./admin.php?page=$page'>Post an Entry!</a>"
			);
		}
	}
	
	//return loaded data and add $fulldisp flag to the end of the array
	array_push($e, $fulldisp);
	
	return $e;
	
}

// Delete Entries from the database
function deleteEntry($db, $url){
	$sql = "DELETE FROM entries
			WHERE url=?
			LIMIT 1";
	$stmt = $db->prepare($sql);
	return $stmt->execute(array($url));
}

//Create links that allow you to edi tand delete entries
function adminLinks($page, $url){
	// Format the link to be followed for each option
	$editURL = "/simple_blog/admin/$page/$url";
	$deleteURL = "/simple_blog/admin/delete/$url";
	
	// Make a hyperlink and add it to an array
	$admin['edit'] = "<a href=\"$editURL\">edit</a>";
	$admin['delete'] = "<a href=\"$deleteURL\">delete</a>";
	
	return $admin;
}


//clean up data from the database
function sanitizeData($data){
	//if $data is not an array, run strip_tags()
	if(!is_array($data)){
		//remove all tags except the <a> tag
		return strip_tags($data, "<a>");
	}
	//if $data is an array, process each element
	else {
		//call sanitizeData recursively for each array element
		return array_map('sanitizeData', $data);
	}
}

function makeUrl($title){
	$patterns = array(
			'/\s+/',
			'/(?!-)\W+/'
	);
	$replacements = array('-', '');
	return preg_replace($patterns, $replacements, strtolower($title));
}

function confirmDelete($db, $url){
	$e = retrieveEntries($db, '', $url);
	
	return <<<FORM

<form action="/simple_blog/admin.php" method="post">
	<fieldset>
		<legend>Are you sure</legend>
		<p>Are you sure you want to delete the entry "$e[title]"</p>
		<input type="submit" name="submit" value="Yes" />
		<input type="submit" name="submit" value="No" />
		<input type="hidden" name="action" value="delete" />
		<input type="hidden" name="url" value="$url" />
	</fieldset>
</form>
FORM;
}
	
function formatImage($img=NULL, $alt=NULL)
{
	if(!empty($img))
	{
		return '<img src="'.$img.'" alt="'.$alt.'" /><br />';
	}
	else 
	{
		return NULL;
	}
}

function createUserForm()
{
	return <<<FORM
<form action="/simple_blog/inc/update.inc.php" method="post">
	<fieldset>
		<legend>Create a New Admin</legend>
		<label>Username
			<input type="text" name="username" maxlength="75" />
		</label>
		<label>Password
			<input type="password" name="password" />
		</label>
		<input type="submit" name="submit" value="Create" />
		<input type="submit" name="submit" value="Cancel" />
		<input type="hidden" name="action" value="createuser" />
	</fieldset>
</form>
FORM;
}

function shortenUrl($url, $login=/*"ENTER EMAIL HERE"*/, 
					$appkey=/*"ENTER API KEY HERE"*/)
{
	// Format a call to the bit.ly API
	$api = 'http://api.bit.ly/v3/shorten?login='.$login.'&apiKey='.$appkey.'&uri='.urlencode($url).'&format=xml';

	// Load the response
	$response = file_get_contents($api);

	// Parse output
	$bitly = simplexml_load_string($response);
	return $bitly->results->nodeKeyVal->shortUrl;
}

function postToTwitter($title)
{
	$full = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$short = shortenUrl($full);
	$status = $title . ' ' . $short;
	return 'http://twiter.com?status='.urlencode($status);
}