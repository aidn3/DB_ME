<?PHP

class DB_ME {
	//Plugin needed to use encrypter
	private $DB_PASS = "DB_ME"; //Text to use with verification KEY;
	private $DB_VER = "1.2"; // CLASS version ([FIRST.SECOND] FIRST is important, it will only increase when new way, function(s) or method used to control the data)
	protected $FileRes; // FILE path
	protected $FileResTmp; //TMP FILE path
	private $Encrypt; // Array with all info needed to encrypt and decrypt data
	private $Error = false; // Variable to save ERROR as text
	private $ResReq = false; // Variable not used yet
	
	function __construct($File, $Encrypt = ""){
		if(!file_exists($File)) {
			if(!file_put_contents($File,".")) return $this->SetError("can not create '$File'");
		}
		if(!@is_file($File)) return $this->SetError("'$File' is not file");

		$File = realpath($File); // Full path needed to not get error when change DIR
		$this->FileRes = $File;
		$this->FileResTmp = $File.".tmp";
		$this->SetEncrypt($Encrypt);
		$this->ResReq = false; // not used
		return $this;
	}
	
	// ON Secess return $this, otherwise FASLE [ERROR]
	function MakeTable($Array){
		if(!is_array($Array)) return $this->SetError("Variable must be Array");
		$Names = "";
		foreach($Array as $name){$Names .= $this->Convert($name).'|';} //Getting the COLUMENs and Encrypt them
		
		$PASS = ($this->DB_VER)."|".$this->Convert($this->DB_PASS)."\r\n"; // encrypt the shema
		$Names = substr($Names,0,-1)."\r\n";
		$RES = fopen($this->FileRes,"w");
		if(!$RES) return $this->SetError("Couldn't open '".$this->FileRes."'");
		fwrite($RES,$PASS);
		fwrite($RES,$Names);
		fclose($RES);
		return $this;
	}
	
	function GetColumen(){
		$RES = fopen($this->FileRes,"a+");
		if(!$RES) return $this->SetError("Couldn't open '".$this->FileRes."'");
		$Cul = $this->GetCul($RES);
		if(!$Cul) return $this->SetError("Couldn't load Culomens");
		return $Cul;
	}

	function SetEncrypt($Key){
		if($Key == "") {$this->Encrypt = ["key"=>""];return $this;} //if Password set to "" (empty) it will remove it from the action
		while(strlen($Key) < 32) $Key .= "\0"; // this can change to 16, 32 and 64
		$this->Encrypt = array("cipher"=>MCRYPT_RIJNDAEL_256, "mode"=>MCRYPT_MODE_ECB,"key"=>$Key);
		$this->Encrypt["iv"] = mcrypt_create_iv(mcrypt_get_iv_size($this->Encrypt["cipher"], $this->Encrypt["mode"]), MCRYPT_RAND);
		return $this;
	}
	
	function GetError(){
		$Er = $this->Error;
		$this->Error = false;
		return $Er;
	
	}
	
	function SelectData($Array, $offset = -1, $limit = -1){
		$CMD = array();
		$CMD["CMD"] = "select";
		
		if(is_numeric($offset) && $offset >= 0) $CMD["OF"] = $offset;
		if(is_numeric($limit) &&   $limit >= 0) $CMD["LI"] = $limit;

		return $this->GetData($Array, $CMD);
	}
	
	function InsertData($Array){
		if(!is_array($Array)) return $this->SetError("Variable must be Array");
		if(empty($Array)) return $this->SetError("Array must be not Empty");
		$RES = fopen($this->FileRes,"a+");
		if(!$RES) return $this->SetError("Couldn't open '".$this->FileRes."'");
		$Cul = $this->GetCul($RES);
		if(!$Cul) return $this->SetError("Couldn't load Culomens");
		$END = 0;
		if(is_array(@$Array[0])){
			foreach($Array as $ARR){
				if(is_array($ARR)) $END += $this->InsertData_($ARR, $RES, $Cul);
			}
		}else{
			$this->InsertData_($Array, $RES, $Cul);
		}
		fclose($RES);
		return $END;
	}
	
	function DeleteData($Array, $offset = -1, $limit = -1){
		$CMD = array();
		$CMD["CMD"] = "delete";
		
		if(is_numeric($offset) && $offset >= 0) $CMD["OF"] = $offset;
		if(is_numeric($limit) &&   $limit >= 0) $CMD["LI"] = $limit;

		return $this->GetData($Array, $CMD);
	}
	
	function ChangeData($Array, $ArrChange, $offset = -1, $limit = -1){
		$CMD = array();
		$CMD["data"] = $ArrChange;
		$CMD["CMD"] = "change";
		
		if(is_numeric($offset) && $offset >= 0) $CMD["OF"] = $offset;
		if(is_numeric($limit) &&   $limit >= 0) $CMD["LI"] = $limit;

		return $this->GetData($Array,$CMD);
	}

	function DateNow(){
		return date('y-m-d h:i:s a');
	}
	
	//Need to be called from the FIRST line in FILE
	private function GetCul($RES){
		$pass = fgets($RES);
		
		//Check VERSION and PASSWORD
		$Data["data"] = explode("|",$pass);
		$Data["ver"] = explode(".", $Data["data"][0]);
		if(trim($Data["ver"][0]) !== explode(".", $this->DB_VER)[0]) return $this->SetError("Please use the same Version File: ".$Data["data"][0]. " THIS: ".$this->DB_VER);
		if(trim($Data["data"][1]) !== $this->Convert($this->DB_PASS)) return $this->SetError("Password ERROR");

		$Data["table"] = fgets($RES);
		$Cul = explode("|",$Data["table"]);
		if(!$Cul) return $this->SetError("Couldn't load Culomens from API");
		$R = array();
		foreach($Cul as $Cu){$R[] = $this->Deconvert($Cu);}
		return $R;
	}
	
	private function InsertData_($Array, $RES, $Cul){
		$Names = "";
		$R = "";
		foreach($Cul as $Cu){
			$R .= @$this->Convert($Array[$Cu])."|";
		}
		$Names = substr($R,0,-1)."\r\n";
		if (@fwrite($RES, $Names)) return 1;
		return 0;
	}
	
	//The CORE for the must ACTIONs (DELETE, SELECT, CHANGE)
	private function GetData($Array, $CMD){
		if(!is_array($Array)) return $this->SetError("Variable must be Array");
		
		//Open FILEs
		$RES = fopen($this->FileRes,"r");
		if(!$RES) return $this->SetError("Couldn't open '".$this->FileRes."'");

		if($CMD["CMD"] == "delete" or $CMD["CMD"] == "change"){
			if(file_exists($this->FileResTmp) && !@unlink($this->FileResTmp)) return $this->SetError("Couldn't delete old tmp file"); 
			
			$RES1 = fopen($this->FileResTmp,"w");
			if(!$RES1) return $this->SetError("Couldn't open '".$this->FileRes."'");
			if(!fputs($RES1,($this->DB_VER)."|".$this->Convert($this->DB_PASS)."\r\n")) return $this->SetError("couln't write Shema data");
		}

		//Get Columen(s)
		$Cul = $this->GetCul($RES);
		if($Cul === false) return $this->SetError("Couldn't load Culomens");
		foreach($Cul as $cu){@($Data .= $this->Convert($cu)."|");}

		//Write Columen to new FILE (if it needed)
		if($CMD["CMD"] == "delete" or $CMD["CMD"] == "change") {
			if(!fputs($RES1,substr($Data,0,-1)."\r\n")) return $this->SetError("couln't write table data");
		}

		//Sort data with array to make one array with the requested data
		$R = array(); // This is imporant
		if($CMD["CMD"] == "change") $ExtraData = array();
		foreach($Cul as $cu){
			if(@isset($Array[$cu])){
				$R[] = $this->Convert($Array[$cu]);
			}else{$R[] = "";}
			if($CMD["CMD"] == "change") {if(isset($CMD["data"][$cu])){$ExtraData[] = $this->Convert($CMD["data"][$cu]);}else{$ExtraData[] = "";}}
		}
		//@var_dump($R,$ExtraData);


		$END = array(); //Result at the end
		$counts = 0; //All loop(s);
		$counts2 = 0; //Nr the equal data
		$counts3 = 0;  //Nr worked on

		//Start fetching data and do ACTION(s)
		while(($line = fgets($RES)) !== false){
			$counts++;
			$CHECK = true; //If DATA is equals the requested

			//Getting data and check if it equals the requested data
			$data = explode("|",$line);
			for($i = 0;$i < count($R);$i ++){
				if(($R[$i] !== trim($data[$i])) && ($R[$i] !== "")) $CHECK = false; // FALSE if it is not
			}
			if($CHECK) $counts2++; // Increase if it equal

			$data2 = array();
			if($CMD["CMD"] == "delete" && !$CHECK) {
				if(!fputs($RES1,$line)){return $this->SetError("Can not write Data at Nr. ".$counts);};
			}
			if($CMD["CMD"] == "change"){
				//if($CHECK && ((isset($CMD["OF"])) && ($CMD["OF"] >= $counts2))){
					for($i = 0;$i < count($R); $i++){
						$data2[] = $data[$i];
					}
					if($CHECK){
						$line = "";
						for($i=0;$i<count($R);$i++){
							if($ExtraData[$i] == ""){
								$line .= $data[$i]."|";
							} else{
								$line .= $ExtraData[$i]."|";
							}
						}
						$line = substr($line,0,-1);
					}
				//}
				if(!fputs($RES1,trim($line)."\r\n")){return $this->SetError("Can not write Data at Nr. ".$counts);}
			}
			if($CMD["CMD"] == "select" && $CHECK) {
				if((isset($CMD["OF"])) && ($CMD["OF"] >= $counts2)) continue;
				if((isset($CMD["LI"])) && ($CMD["LI"] < $counts3)) break;
				for($i = 0;$i < count($R); $i++){
					$data2[$Cul[$i]] = $data[$i];
				}
				$END[$counts] = $this->GetDataDeconvert($data2); //$counts used to give the number of row 
			}
			$counts3++;
		}
		
		@fclose($RES);
		@fclose($RES1);

		if($CMD["CMD"] == "delete" or $CMD["CMD"] == "change"){
			if(@copy($this->FileResTmp, $this->FileRes)) {
				@unlink($this->FileResTmp);
				return $counts2;
			}else{
				return $this->SetError("ERROR copy from tmp file to ORG. file");
			}
		}
		return $END;
	}
	
	private function GetDataDeconvert($Array){
		$result = array();
		foreach($Array as $K=>$V){
			$result[$K] = $this->Deconvert($V);
		}
		return $result;
	}
	
	private function Convert($Data){
		//If the was no password it will skip the encryption
		if($this->Encrypt["key"] !== ""){
			//$IV = mcrypt_create_iv(mcrypt_get_iv_size($this->Encrypt["cipher"], $this->Encrypt["mode"]), MCRYPT_RAND);
			return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->Encrypt["key"], $Data,MCRYPT_MODE_ECB, $this->Encrypt["iv"])));
		}
		return base64_encode($Data);
	}
	
	private function Deconvert($Data){
		//If the was no password it will skip the encryption
		if($this->Encrypt["key"] !== ""){
			//$IV = mcrypt_create_iv(mcrypt_get_iv_size($this->Encrypt["cipher"], $this->Encrypt["mode"]), MCRYPT_RAND);
			return trim(mcrypt_decrypt($this->Encrypt["cipher"], $this->Encrypt["key"], base64_decode($Data), $this->Encrypt["mode"], $this->Encrypt["iv"]));
		}
		return base64_decode($Data);
	}
	
	private function SetError($msg){
		$this->Error .= $msg."\r\n";
		return false;
	}
}
?>
