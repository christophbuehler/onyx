<?php
$adminKey = '|d86s(79sa8a9z)zbz8as';

require_once 'config.php';

error_reporting(-1);

ini_set('display_errors', 'On');

$error = '';
$success = '';

$savedValues = array(
  'key' => '',
  'pass' => '',
  'user' => 'root',
  'location' => '',
  'host' => 'localhost',
  'dbName' => '',
  'appName' => '',
  'appNameShort' => '',
  'domain' => '',
  'extensions' => '',
);

$extensions = get_extensions();

function install_onyx()
{
    global $adminKey;
    global $error;
    global $success;
    global $savedValues;

    // check entries
    $requiredFields = ['key', 'domain', 'location', 'host', 'user', 'dbName', 'appName', 'appNameShort'];

    // save entered values
    foreach ($savedValues as $field => $value) {
        if (isset($_POST[$field])) {
            $savedValues[$field] = $_POST[$field];
        }
    }

    // errors
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || strlen(trim($_POST[$field])) == 0) {
            $error = 'Missing field: '.$field;
            return;
        }
    }

    /* all fields have valid input */

    // check key
    if ($savedValues['key'] != $adminKey) {
        $error = 'Bad admin key.';
        return;
    }

    // copy base template
    $src = 'base';
    $dst = '../'.$savedValues['location'];
    recurse_copy($src, $dst);

    // modify config file
    $fileContents = file_get_contents('base/config.php');
    $relPath = str_replace('\\', '/', find_relative_path(realpath(dirname(__FILE__).'/'.$dst), dirname(__FILE__)));

    // extensions
    $extensionsFormatted = '';

    $extensions = strlen($savedValues['extensions']) > 0 ? json_decode($savedValues['extensions']) : array();

    foreach ($extensions as $extension) {
        $extensionsFormatted .= "'".$extension."', ";
    }

    $extensionsFormatted = trim($extensionsFormatted, ', ');

    $replaceActions = array(
      'db_host' => $savedValues['host'],
      'db_name' => $savedValues['dbName'],
      'db_user' => $savedValues['user'],
      'db_pass' => $savedValues['pass'],
      'domain' => $savedValues['domain'],
      'site_name' => $savedValues['appName'],
      'site_name_short' => $savedValues['appNameShort'],
      'onyx_repository' => $relPath.'/',
      'extensions' => $extensionsFormatted,
    );

    foreach ($replaceActions as $replAction => $value) {
        $fileContents = str_replace('{'.$replAction.'}', $value, $fileContents);
    }

    $fp = fopen($dst.'/config.php', 'wb');

    fwrite($fp, "<?php\n".$fileContents);

    fclose($fp);

    $success = 'Successfully created Onyx instance.';
}

function get_extensions()
{
    $extensions = array();

    foreach (glob('extensions/*') as $file) {
        if (filetype($file) != 'dir') {
            continue;
        }

        array_push($extensions, basename($file));
    }

    return $extensions;
}

function find_relative_path($frompath, $topath)
{
    $from = explode(DIRECTORY_SEPARATOR, $frompath); // Folders/File
    $to = explode(DIRECTORY_SEPARATOR, $topath); // Folders/File
    $relpath = '';

    $i = 0;

    // Find how far the path is the same
    while (isset($from[$i]) && isset($to[$i])) {
        if ($from[$i] != $to[$i]) {
            break;
        }

        ++$i;
    }

    $j = count($from) - 1;

    // Add '..' until the path is the same
    while ($i <= $j) {
        if (!empty($from[$j])) {
            $relpath .= '..'.DIRECTORY_SEPARATOR;
        }

        --$j;
    }

    // Go to folder from where it starts differing
    while (isset($to[$i])) {
        if (!empty($to[$i])) {
            $relpath .= $to[$i].DIRECTORY_SEPARATOR;
        }

        ++$i;
    }

    // Strip last separator
    return substr($relpath, 0, -1);
}

function recurse_copy($src, $dst)
{
    $dir = opendir($src);

    @mkdir($dst);

    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src.'/'.$file)) {
                recurse_copy($src.'/'.$file, $dst.'/'.$file);
            } else {
                copy($src.'/'.$file, $dst.'/'.$file);
            }
        }
    }

    closedir($dir);
}

if (isset($_POST['installOnyx'])) {
    install_onyx();
}

?>

<!DOCTYPE html>
<html>
<head>
	<style>
	body { margin: 0; padding: 0; font-family: 'Tahoma'; }
	h1 { margin: 0 0 16px; color: #2288bb; font-weight: normal; }
	#container1 { margin: 40px auto; width: 960px; }
	.area { height: 38px; background: #ffefe2; margin: 8px 0; }
	.area label { display: block; }
	.area label span { width: 180px; padding: 8px; display: inline-block; color: #565656; }
	.area input { width: 180px; color: #222; margin-left: 32px; border: 0; background: #fff; line-height: 30px; padding: 0 8px; }
	.area input:active{ backrgound: #fff; }
	legend { color: #565656; }
	.error { color: red; }
	.success { color: green; }
	.submit { background: linear-gradient(#33dd88, #33bd68); }
	.btn, .submit { cursor: pointer; color: #fff; border: 1px solid #888; border-radius: 4px; padding: 0 8px; line-height: 24px; }
	.btn { background: linear-gradient(#999, #666); }
	.desc { color: #888; font-size: 12px; width: auto !important; }
    #extensionSelection { border: 1px solid #aaa; height: 120px; }
    .extension { cursor: default; border-radius: 4px; border: 1px solid #333; background: linear-gradient(#111, #272727); color: #fff; line-height: 20px; padding: 0 8px; font-size: 12px; display: inline-block; margin: 4px; }
    .extension[data-sel=true] { background: linear-gradient(#22b85a, #22d86a); border-color: #12883a; }
    #extensionCaption { color: #565656; display: inline-block; margin: 4px 0; }
    </style>
    <script>
        window.onload = init;
        function init() {
            var savedVal = document.getElementById('extensions').value,
                savedArr = savedVal.length > 0 ? JSON.parse(savedVal) : [], locationInput;

            savedArr.map(function(text) {
                Array.prototype.slice.call(document.getElementsByClassName('extension')).map(function(el) {
                    if (el.innerHTML != text) return;
                    el.setAttribute('data-sel', 'true');
                });
            });

            Array.prototype.slice.call(document.getElementsByClassName('extension')).map(function(el) {
                el.addEventListener('click', function() {
                    var extensions = [];
                    if (this.getAttribute('data-sel') == 'true')
                        this.setAttribute('data-sel', 'false');
                    else
                        this.setAttribute('data-sel', 'true');

                    Array.prototype.slice.call(document.getElementsByClassName('extension')).map(function(el) {
                        if (el.getAttribute('data-sel') == 'true')
                            extensions.push(el.innerHTML);
                    });

                    document.getElementById('extensions').value = JSON.stringify(extensions);
                });
            });

            locationInput = document.getElementById('location');

            setAdditionalPathInfo(locationInput.value);

            locationInput.addEventListener('keyup', function() {
            	setAdditionalPathInfo(this.value);
            });

            getLocalStorageKey();

            document.getElementById('localStorageBtn').addEventListener('click', function(evt) {
            	evt.preventDefault();
            	evt.stopPropagation();

		setLocalStorageKey(document.getElementById('key').value);
            });
        }
        function setLocalStorageKey(text) {
        	if (text.trim() == '') {
        		alert("Key value is empty.");
        		return;
        	}
        	localStorage.setItem('onyx-installation-key', text);
        	alert("Key saved in local storage.");
        }
        function getLocalStorageKey() {
                var key = document.getElementById('key'),
            	    lclKey = localStorage.getItem('onyx-installation-key');
            	if (key.value.trim() != '' || !lclKey) return;
            	key.value = lclKey;
        }
       	function setAdditionalPathInfo(text) {
       		document.getElementById('additionalPathInfo').innerHTML = text;
       	}
    </script>
</head>
<body>
	<div id="container1">
		<h1>Onyx installation</h1>
		<form method="post">
			<div class="area">
				<label>
					<span>admin key</span>
					<input name="key" id="key" value="<?php echo $savedValues['key']; ?>" type="text">&nbsp;&nbsp;<button id="localStorageBtn" class="btn">save in local storage</button>
				</label>
			</div>
			<div class="area">
				<label>
					<span>location</span>
					<input name="location" id="location" value="<?php echo $savedValues['location']; ?>" type="text">
					<span class="desc"><?php echo realpath(dirname(__FILE__).'/../'); ?>/<b id="additionalPathInfo"></b></span>
				</label>
			</div>
			<fieldset>
				<legend>Database</legend>
				<div class="area">
					<label>
						<span>Name</span>
						<input name="dbName" value="<?php echo $savedValues['dbName']; ?>" type="text">
					</label>
				</div>
				<div class="area">
					<label>
						<span>Host</span>
						<input name="host" value="<?php echo $savedValues['host']; ?>" type="text">
					</label>
				</div>
				<div class="area">
					<label>
						<span>User</span>
						<input name="user" value="<?php echo $savedValues['user']; ?>" type="text">
					</label>
				</div>
				<div class="area">
					<label>
						<span>Password</span>
						<input name="pass" value="<?php echo $savedValues['pass']; ?>" type="password">
					</label>
				</div>
			</fieldset>
			<fieldset>
				<legend>Application</legend>
				<div class="area">
					<label>
						<span>Domain</span>
						<input name="domain" value="<?php echo $savedValues['domain']; ?>" type="text">
					</label>
				</div>
				<div class="area">
					<label>
						<span>Site Name</span>
						<input name="appName" value="<?php echo $savedValues['appName']; ?>" type="text">
					</label>
				</div>
				<div class="area">
					<label>
						<span>Site Name Short</span>
						<input name="appNameShort" value="<?php echo $savedValues['appNameShort']; ?>" type="text">
					</label>
				</div>
			</fieldset>
            <input type="hidden" name="extensions" value='<?php echo $savedValues['extensions']; ?>' id="extensions">
            <span id="extensionCaption">Extensions</span>
            <div id="extensionSelection">
                <?php foreach ($extensions as $extension) {
    echo "<div class='extension'>".$extension.'</div>';
} ?>
            </div>

			<br/>
			<input type="submit" name="installOnyx" class="submit" value="install">
			<br/><br/>
			<div class="error"><?php echo $error; ?></div>
			<div class="success"><?php echo $success; ?></div>
		</form>
	</div>
</body>
</html>
