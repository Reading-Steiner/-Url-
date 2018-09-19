
<html>
<head>
	<meta charset = "utf-8"/>
	<title>短Url生成器</title>
</head>
<body>
<SCRIPT LANGUAGE="JavaScript">
   function myCopy(){
		var ele = document.getElementById("shortUrl");
        ele.select();
        document.execCommand("Copy");
    }
</SCRIPT>
<?php
	//获取get传值（长Url）//
	$Url=$_GET['longURL'];
	htmlentities($Url,ENT_QUOTES);
	$localhost = $_SERVER['HTTP_HOST'];
	$shortURL = "";
	//初始化标志//
	$ifExist = false;
	//初始化数据//
	$servername = "localhost";
	$username = "root";
	$password = "123456";
	$dbname = "urlkeyvalue";
	//连接数据库//
	// 创建连接
	$conn = new mysqli($servername, $username, $password, $dbname);	
	// 检测连接
	if ($conn->connect_error) {
		//die("连接失败: " . $conn->connect_error);
	}else{
		$sql ='SELECT longURL,shortURL from Cache where longURL= "'.$Url.'"';
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			//（数据库Cache表）读取短Url，没有再搜索总表//
			if($row = $result->fetch_assoc()) {
				$shortURL = $row["shortURL"];
				$ifExist = true;
			}
			
		} else {
			$ifExist = false;
		}
		$conn->close();
	}
	if($ifExist==false){
		//连接数据库//
		// 创建连接
		$conn = new mysqli($servername, $username, $password, $dbname);	
		// 检测连接
		if ($conn->connect_error) {
			die("连接失败: " . $conn->connect_error."尝试直接生成");
		}else{
			//搜索数据库//
			$sql = 'SELECT longURL,shortURL from KeyValue where longURL= "'.$Url.'"';
			$result = $conn->query($sql);
			if ($result->num_rows > 0) {
				//（数据库）读取短Url//
				if($row = $result->fetch_assoc()) {
					$shortURL = $row["shortURL"];
					$ifExist = true;
				}
				
			} else {
				$ifExist = false;
			}
		}
		$conn->close();
	}
	if($ifExist == false){
		//生成发号器种子//
		echo $time = microtime(true)*10000;		//生成时间戳
		$limit = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$base = $time*100;					//基数
		$weights = 0;						//权值
		//连接数据库//
		// 创建连接
		$conn = new mysqli($servername, $username, $password, $dbname);	
		// 检测连接
		if ($conn->connect_error) {
			//die("连接失败: " . $conn->connect_error);
		}else{
			$sql = "SELECT id from cache where id =(select max(id) from cache)";
			$result = $conn->query($sql);
			//var_dump($result);
			if ($result->num_rows > 0) {
				//（数据库）生成权值//
				if($row = $result->fetch_assoc()) {
					$weights = $row["id"]+1;
				}
			}else {
				$weights = 0;
			}
		}
		$conn->close();
		
		
		
		while($ifExist == false){
			$shortURL = "";
			$base=$time*100;
			//（发号器）生成短Url//
			$base = $base+fmod($weights,100);
			while($base >= 1){
				$shortURL = substr($limit,fmod($base,62),1).$shortURL;
				$base = $base/62;
			}
			if(file_exists($shortURL)==false){
				mkdir("$shortURL");
				$file = fopen ("$shortURL\\index.php", "w");
				if (!$file) {
					echo "<p>Unable to open remote file for writing.\n";
					exit;
				}
				fwrite ($file, "<?php  header('HTTP/1.1 301 Moved Permanently');  header('Location:".$Url."');  ?>");
				fclose ($file);
				
				
				//链接数据库//
				// 创建连接
				$conn = new mysqli($servername, $username, $password, $dbname);
				// 检测连接
				if ($conn->connect_error) {
					//die("连接失败: " . $conn->connect_error);
				} 
				//存储短Url与长Url钥匙对//
				//存储到KV表
				$sql = 'INSERT INTO keyvalue(shortURL,longURL)VALUES ("'.$shortURL.'","'.$Url.'")';
				if ($conn->query($sql) === TRUE) {
					//存储到Cache表
					$sql = 'insert into cache(shortUrl,longUrl)values("'.$shortURL.'","'.$Url.'")';
					if ($conn->query($sql) === TRUE) {
						//限制Cache表长度//
						$sql = "SELECT id from cache where id =(select max(id) from cache)";
						$result = $conn->query($sql);
						if ($result->num_rows > 0) {
							if($row = $result->fetch_assoc()) {
								echo $maxID = $row["id"];
								if($maxID>=10){
									$sql = "SELECT id from cache where id =(select min(id) from cache)";
									$result = $conn->query($sql);
									if ($result->num_rows > 0) {
										if($row = $result->fetch_assoc()) {
											$minID = $row["id"];
											if($maxID-$minID>=10){
												$sql = "delete from cache where id = $minID";
												mysqli_query($conn,$sql);
											}
										}
									}
								}
							}
						}
					}else {
						//echo "Error: " . $sql . "<br>" . $conn->error;
					}
				}else {
					//echo "Error: " . $sql . "<br>" . $conn->error;
				}
				
				$conn->close();
				$ifExist = true;
			} else {
				//echo "文件已存在,Url已被使用"
				//加权再相加//
				$weights=fmod($weights+9,100);
			}
		}
	}
	//创建输出框//
	echo '<br/>';
	echo '<br/>';
	echo '<br/>';
	echo '<br/>';
	echo '<br/>';
	echo '<br/>';
	echo '<br/>';
	echo '<br/>';
	echo '<form>';
	echo '<p>----------------------------------------长URL：<input type="url" name="Url" value="'.$Url.'" Readonly="Readonly" size="100" ><p/>';
	echo '<p>----------------------------------------短URL：<input type="url" name="Url" value= "'.$localhost.'/'.$shortURL.'" id="shortUrl" Readonly="Readonly" size="100" >';
?>
<input type="button" value="复制" onClick="myCopy()"/> <p/>
</form>
</body>
</html>

