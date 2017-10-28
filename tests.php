<?PHP
	//Add lib
	require "DB_ME.php";
	
	//Create new OBJECT
	$DB_ME = new DB_ME("DB.bin");
	if (!$DB_ME) die("ERROR Creating DB");
	
	//(Optional) Set  encrytion
	$DB_ME->SetEncrypt("PASSword");
	
	//Create a TABLE
	$Result = $DB_ME->MakeTable(array("users","Pass","emails","EXtra"));
	if(!$Result) die($DB_ME->GetError());
	
	//INSERT multiple data
	$DATA = array();
	for($i = 0;$i < 1000; $i++){
		$DATA[] = array("users"=>"My.Name","emails"=>"email.me","Pass"=>"Pass.Word");
	}
	$Result = $DB_ME->InsertData($DATA);
	if(!$Result) die($DB_ME->GetError());
	echo $Result ." inserted";
	unset($DATA, $i);
	
	//INSERT one data
	$DB_ME->InsertData(array("users"=>"aidmn","emails"=>"F@f.co"));
	
	//CHANGE data
	$Result = $DB_ME->ChangeData(array("users"=>"My.Name"),array("Pass"=>"Pass.ME"));
	if(!$Result) die($DB_ME->GetError());
	echo $Result ." changed";
	
	//SELECT data
	$Result = $DB_ME->SelectData(["users"=>"My.Name"]);
	if(!$Result) die($DB_ME->GetError());
	//var_dump($Result);
	unset($Result);
	
	//DELETE data
	$Result = $DB_ME->DeleteData(["users"=>"My.Name"]);
	if(!$Result) die($DB_ME->GetError());
	echo $Result ." deleted";
	
	//SELECT data
	$Result = $DB_ME->SelectData(["users"=>"aidmn"]);
	if(!$Result) die($DB_ME->GetError());
	var_dump($Result);
?>