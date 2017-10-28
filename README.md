# DB_ME
It's like SQLite, but without any extra Lib(s) or anything just one small file.
It could be used to store unlimited data (Length, row numbers).
It is like impossible to do a successful 'data injection'.

#### NOTE: THERE IS NO WARRANTY

## 1. Get Started
```PHP
//include the Lib in Project
require "DB_ME.php";

//create new Object
$DB_ME = new DB_ME("DB_ME.bin");

//(optional) Set Encrypt if it necessary
//$DB_ME->SetEncrypt("Password");

//Create a Table 
$DB_ME->MakeTable(["users","emails","Pass"]);
```

## 2. Insert, select, delete and change data 
Insert data
```PHP
$Result = $DB_ME->InsertData(array("users"=>"my_name","emails"=>"F@f.co"));
```

delete data
```PHP
$Result = $DB_ME->DeleteData(array("users"=>"my_name"));
```

select data
```PHP
$Result = $DB_ME->SelectData(array("users"=>"my_name"));
```

change data
```PHP
$Result = $DB_ME->ChangeData(array("Pass"=>"SavedPassword"), array("emails"=>"NewEmail"));
```

<hr>

## 3. On (error, successful, bad data, failed to) 
On ERROR it will always return FALSE
```PHP
$Result = $DB_ME->MakeTable(["users","emails","Pass"]);
if(!$Result) var_dump($DB_ME->GetError());
```

<strong>->GetError()</strong> if there was no ERROR it will return FALSE
<hr>

## 4. IMPORTENT Notes 
<strong> A.</strong> First Parameter of [INSERT, SELECT, DELETE, CHANGE] should always be ARRAY  
<strong> B.</strong> Password cannot change later, yet.  
<strong> C.</strong> On [DELETE, CHANGE], a FILE will be created with the name [FILE_NAME + ".tmp"]  
<strong> D.</strong> If a new TABLE created, the old one with all the data will be DELETED  
<strong> E.</strong> the ENCRYPTION with INSERT, SELECT will use a lot of resourses and time, 1000 rows or less is good  
<strong> F.</strong> if a variable in an ARRAY not found in the TABLE will be ignored  
<strong> G.</strong> be careful of using SELECT and expect returning more than 10,000 rows  
<hr>

## 5. Reference 
#### A. Create a new DB_ME
```PHP
$DB_ME = new DB_ME(FileName, [Optional] Password);
```
If there IS a DIR with the same name it will return an ERROR  
If can NOT CREATE the file will return also ERROR  
otherwise it will return OBJECT  
		
	
#### B. Set ENCRYPTION
```PHP
$DB_ME->SetEncrypt(Password);
```
It is optional  
it MUSST set the ENCRYPTION BEFORE take ANY action [create TABLE, SELECT or …]  
if there is ENCRYPTION inside the DB and an action took with [wrong or no] password it will return FALSE with an ERROR  
BE CARFUL with using encryption it my slow the process  
	
#### C. Create a TABLE
```PHP
$DB_ME->MakeTable(ARRAY);
```
Parameter MUSST always ARRAY (ex. ["users","emails","Pass"]) 
It will return ERROR when it cannot open FILE 
Creating new TABLE means deleting the OLD one 
If there is no ERROR it will return the OBJECT 
	
#### D. INSERT DATA
```PHP
$Result = $DB_ME->InsertData(array);
```
Parameter should always be ARRAY (ex. ["users"=>"my_name","emails"=>"F@f.co"])  
It can insert multiple Parameters (ex. [["users"=>"my_name","emails"=>"F@f.co"], ["users"=>"my_name","emails"=>"F@f.co"]])  
It will return the Number of inserted rows or ERROR (when there is)  

	
#### E. SELECT DATA
```PHP
$Result = $DB_ME->SELECT(array, [optional] offset, [optional] limit);
```		
array ex. ["users"=>"my_name"]  
if array is empty it will return EVERYTHING  
it will return an array with EVERY info in every selected row (ex. [["users"=>"my_name","emails"=>"F@f.co"], ["users"=>"my_name","emails"=>"F@f.co"]])  
otherwise it will return ERROR  

	
#### F. DELETE DATA
```PHP
$Result = $DB_ME->DeleteData(array, [optional] offset, [optional] limit);
```	
If the ARRAY is EMPTY it will delete EVERY row  
It will return the number of deleted row  
otherwise it return an ERROR  

	
#### G. CHANGE DATA
```PHP
$Result = $DB_ME->ChangeData(array, array, [optional] offset, [optional] limit);
```
first Parameter is for searched data (ex. ["users"=>"my_name"])  
Second Parameter is for to change data (ex. ["emails"=>"email.me"])  
it will return the number of changed row(s) or ERROR  
	
#### H. ERROR
```PHP
$Result = $DB_ME->GetError();
```		
It will return [STRING (when there is ERROR), FALSE]  

<hr>

## 6. Full example 
```PHP
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
```

<hr>

## 7. How does it work
File will be like a table:
```
version|Encrypted_Key
Culmen|Culmen|Culmen
Data|Data|data
Data|Data|data
Data|Data|data
```
-Every line have more than one info (separated by "|")

-First line [1. the Version of DB_ME, 2. Encrypted KEY]  
There is always key to know if there is password or not and if it is wrong  
Data will always encrypt by "base64", so when there is no password it will encrypt only by it. 

-Second line has the name of the Columns  

-The rest are the data sorted as the Columns  
If a Column in row has no data it will be empty (ex. "Data||DATA" or "DATA|DATA|")  
