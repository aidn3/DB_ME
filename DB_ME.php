<?PHP

class DB_ME {
	private $DB_PASS = "DB_ME";
	private $DB_VER = "1.1";
	protected $FileRes;
	protected $FileResTmp;
	private $Encrypt;
	private $Error = false;
	private $ResReq = false;
	
	function __construct($File, $Encrypt = ""){
		if(!file_exists($File)) {
			if(!file_put_contents($File,".")) return $this->SetError("can not create '$File'");
		}
		if(!@is_file($File)) return $this->SetError("'$File' is not file");
		$this->FileRes = $File;
		$this->FileResTmp = $File.".tmp";
		$this->SetEncrypt($Encrypt);
		$this->ResReq = false;
		return $this;
	}
	
	function MakeTable($Array){
		if(!is_array($Array)) return $this->SetError("Variable must be Array");
		$Names = "";
		foreach($Array as $name){$Names .= $this->Convert($name).'|';}
		
		$PASS = ($this->DB_VER)."|".$this->Convert($this->DB_PASS)."\r\n";
		$Names = substr($Names,0,-1)."\r\n";
		$RES = fopen($this->FileRes,"w");
		if(!$RES) return $this->SetError("Couldn't open '".$this->FileRes."'");
		fwrite($RES,$PASS);
		fwrite($RES,$Names);
		fclose($RES);
		return $this;
	}
	
	function SetEncrypt($Key){
		if($Key !== ""){while(strlen($Key) < 32) $Key .= "\0";}
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
		if($offset >= 0) $CMD["OF"] = $offset;
		if($limit >= 0) $CMD["LI"] = $limit;
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
				if(is_array($ARR)) $END += $this->InsetData_($ARR, $RES, $Cul);
			}
		}else{
			$this->InsetData_($Array, $RES, $Cul);
		}
		fclose($RES);
		return $END;
	}
	
	function DeleteData($Array, $offset = -1, $limit = -1){
		$CMD = array();
		$CMD["CMD"] = "delete";
		if($offset >= 0) $CMD["OF"] = $offset;
		if($limit >= 0) $CMD["LI"] = $limit;
		if(file_exists($this->FileResTmp) && !@unlink($this->FileResTmp)) return $this->SetError("Couldn't delete old tmp file"); 
		if(!@copy($this->FileRes,$this->FileResTmp)) return $this->Error("Couldn't make tmp file");
		return $this->GetData($Array, $CMD);
	}
	
	function ChangeData($Array, $ArrChange, $offset = -1, $limit = -1){
		$CMD = array();
		$CMD["data"] = $ArrChange;
		$CMD["CMD"] = "change";
		if($offset >= 0) $CMD["OF"] = $offset;
		if($limit >= 0) $CMD["LI"] = $limit;
		if(file_exists($this->FileResTmp) && !@unlink($this->FileResTmp)) return $this->SetError("Couldn't delete old tmp file"); 
		if(!@copy($this->FileRes,$this->FileResTmp)) return $this->Error("Couldn't make tmp file");
		return $this->GetData($Array,$CMD);
	}
	
	private function DataParser($RES,$CMD){
		//echo print_r(explode("|",$RES),true) . " / ".$this->Convert($this->DB_PASS)."\r\n";
		if($CMD == "pass" && trim(explode("|",$RES)[1]) == $this->Convert($this->DB_PASS)) return true;
		
		return false;
	}
	
	private function GetCul($RES){
		$pass = fgets($RES);
		if(!($this->DataParser($pass,"pass"))) return $this->SetError("Password Error.");
		$Data = fgets($RES);
		$Cul = explode("|",$Data);
		if(!$Cul) return $this->SetError("Couldn't load Culomens from API");
		$R = array();
		foreach($Cul as $Cu){$R[] = $this->Deconvert($Cu);}
		return $R;
	}
	
	private function InsetData_($Array, $RES, $Cul){
		$Names = "";
		$R = "";
		foreach($Cul as $Cu){
			$R .= @$this->Convert($Array[$Cu])."|";
		}
		$Names = substr($R,0,-1)."\r\n";
		if (@fwrite($RES, $Names)) return 1;
		return 0;
	}
	
	private function GetData($Array, $CMD){
		if(!is_array($Array)) return $this->SetError("Variable must be Array");
		
		if($CMD["CMD"] == "select"){
			$RES = fopen($this->FileRes,"r");
			if(!$RES) return $this->SetError("Couldn't open '".$this->FileRes."'");
		}
		if($CMD["CMD"] == "delete" or $CMD["CMD"] == "change"){
			$RES = fopen($this->FileResTmp,"r");
			$RES1 = fopen($this->FileRes,"w");
			if(!$RES) return $this->SetError("Couldn't open '".$this->FileResTmp."'");
			if(!$RES1) return $this->SetError("Couldn't open '".$this->FileRes."'");
		}
		$Cul = $this->GetCul($RES);
		if(!$Cul) return $this->SetError("Couldn't load Culomens");
		foreach($Cul as $cu){@($Data .= $this->Convert($cu)."|");}
		if(($CMD["CMD"] == "delete" or $CMD["CMD"] == "change") && !fputs($RES1,($this->DB_VER)."|".$this->Convert($this->DB_PASS)."\r\n")) return $this->SetError("couln't write Shema data");
		if(($CMD["CMD"] == "delete" or $CMD["CMD"] == "change") && !fputs($RES1,substr($Data,0,-1)."\r\n")) return $this->SetError("couln't write table data");
		$R = array();
		if($CMD["CMD"] == "change") $ExtraData = array();
		foreach($Cul as $cu){
			if(@isset($Array[$cu])){
				$R[] = $this->Convert($Array[$cu]);
			}else{$R[] = "";}
			if($CMD["CMD"] == "change") {if(isset($CMD["data"][$cu])){$ExtraData[] = $this->Convert($CMD["data"][$cu]);}else{$ExtraData[] = "";}}
		}
		//@var_dump($R,$ExtraData);
		$END = array();
		$counts = 0; //All loop(s);
		$counts2 = 0; //Nr the equal data
		$counts3 = 0;  //Nr worked
		while(($line = fgets($RES)) !== false){
			$counts++;
			$data = explode("|",$line);
			$CHECK = true;
			for($i = 0;$i < count($R);$i ++){
				if(($R[$i] !== trim($data[$i])) && ($R[$i] !== "")) $CHECK = false;
			}
			$data2 = array();
			if($CHECK) $counts2++;
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
				$END[$counts] = $this->GetDataDeconvert($data2);
			}
			$counts3++;
		}
		
		fclose($RES);
		if($CMD["CMD"] == "delete" or $CMD["CMD"] == "change"){@unlink($this->FileResTmp);return $counts2;}
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
		if($this->Encrypt["key"] !== ""){
			//$IV = mcrypt_create_iv(mcrypt_get_iv_size($this->Encrypt["cipher"], $this->Encrypt["mode"]), MCRYPT_RAND);
			return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->Encrypt["key"], $Data,MCRYPT_MODE_ECB, $this->Encrypt["iv"])));
	}
		return base64_encode($Data);
	}
	
	private function Deconvert($Data){
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