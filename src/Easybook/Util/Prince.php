<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Util;

// Class copied from http://www.princexml.com/download/wrappers/
// Copyright (c) 2002 – 2011 YesLogic Pty. Ltd.
class Prince
{
    private $exePath;
    private $styleSheets;
    private $isHTML;
    private $baseURL;
    private $doXInclude;
    private $httpUser;
    private $httpPassword;
    private $logFile;
    private $embedFonts;
    private $compress;
    private $encrypt;
    private $encryptInfo;

    public function __construct($exePath)
    {
	$this->exePath = $exePath;
	$this->styleSheets = '';
	$this->isHTML = false;
	$this->baseURL = '';
	$this->doXInclude = true;
	$this->httpUser = '';
	$this->httpPassword = '';
	$this->logFile = '';
	$this->embedFonts = true;
	$this->compress = true;
	$this->encrypt = false;
	$this->encryptInfo = '';
    }

    // Add a CSS style sheet that will be applied to each document.
    // cssPath: The filename of the CSS style sheet.
    public function addStyleSheet($cssPath)
    {
	$this->styleSheets .= '-s "' . $cssPath . '" ';
    }

    // Clear all of the CSS style sheets.
    public function clearStyleSheets()
    {
	$this->styleSheets = '';
    }

    // Specify whether documents should be parsed as HTML or XML/XHTML.
    // html: True if all documents should be treated as HTML.
    public function setHTML($html)
    {
	$this->isHTML = $html;
    }

    // Specify a file that Prince should use to log error/warning messages.
    // logFile: The filename that Prince should use to log error/warning
    //	    messages, or '' to disable logging.
    public function setLog($logFile)
    {
	$this->logFile = $logFile;
    }

    // Specify the base URL of the input document.
    // baseURL: The base URL or path of the input document, or ''.
    public function setBaseURL($baseURL)
    {
	$this->baseURL = $baseURL;
    }

    // Specify whether XML Inclusions (XInclude) processing should be applied
    // to input documents. XInclude processing will be performed by default
    // unless explicitly disabled.
    // xinclude: False to disable XInclude processing.
    public function setXInclude($xinclude)
    {
	$this->doXInclude = $xinclude;
    }

    // Specify a username to use when fetching remote resources over HTTP.
    // user: The username to use for basic HTTP authentication.
    public function setHttpUser($user)
    {
	$this->httpUser = $user;
    }

    // Specify a password to use when fetching remote resources over HTTP.
    // password: The password to use for basic HTTP authentication.
    public function setHttpPassword($password)
    {
	$this->httpPassword = $password;
    }

    // Specify whether fonts should be embedded in the output PDF file. Fonts
    // will be embedded by default unless explicitly disabled.
    // embedFonts: False to disable PDF font embedding.
    public function setEmbedFonts($embedFonts)
    {
	$this->embedFonts = $embedFonts;
    }

    // Specify whether compression should be applied to the output PDF file.
    // Compression will be applied by default unless explicitly disabled.
    // compress: False to disable PDF compression.
    public function setCompress($compress)
    {
	$this->compress = $compress;
    }

    // Specify whether encryption should be applied to the output PDF file.
    // Encryption will not be applied by default unless explicitly enabled.
    // encrypt: True to enable PDF encryption.
    public function setEncrypt($encrypt)
    {
	$this->encrypt = $encrypt;
    }

    // Set the parameters used for PDF encryption. Calling this method will
    // also enable PDF encryption, equivalent to calling setEncrypt(true).
    // keyBits: The size of the encryption key in bits (must be 40 or 128).
    // userPassword: The user password for the PDF file.
    // ownerPassword: The owner password for the PDF file.
    // disallowPrint: True to disallow printing of the PDF file.
    // disallowModify: True to disallow modification of the PDF file.
    // disallowCopy: True to disallow copying from the PDF file.
    // disallowAnnotate: True to disallow annotation of the PDF file.
    public function setEncryptInfo($keyBits,
				   $userPassword,
				   $ownerPassword,
				   $disallowPrint = false,
				   $disallowModify = false,
				   $disallowCopy = false,
				   $disallowAnnotate = false)
    {
	if ($keyBits != 40 && $keyBits != 128)
	{
	    throw new Exception("Invalid value for keyBits: $keyBits" .
		" (must be 40 or 128)");
	}

	$this->encrypt = true;

        $this->encryptInfo =
		' --key-bits ' . $keyBits .
                ' --user-password="' . $userPassword .
                '" --owner-password="' . $ownerPassword . '" ';

        if ($disallowPrint)
	{
            $this->encryptInfo .= '--disallow-print ';
	}

        if ($disallowModify)
	{
            $this->encryptInfo .= '--disallow-modify ';
	}

        if ($disallowCopy)
	{
            $this->encryptInfo .= '--disallow-copy ';
	}

        if ($disallowAnnotate)
	{
            $this->encryptInfo .= '--disallow-annotate ';
	}
    }

    // Convert an XML or HTML file to a PDF file.
    // The name of the output PDF file will be the same as the name of the
    // input file but with an extension of ".pdf".
    // xmlPath: The filename of the input XML or HTML document.
    // msgs: An optional array in which to return error and warning messages.
    // Returns true if a PDF file was generated successfully.
    public function convert_file($xmlPath, &$msgs = array())
    {
	$pathAndArgs = $this->getCommandLine();
	$pathAndArgs .= '"' . $xmlPath . '"';

	return $this->convert_internal_file_to_file($pathAndArgs, $msgs);
    }

    // Convert an XML or HTML file to a PDF file.
    // xmlPath: The filename of the input XML or HTML document.
    // pdfPath: The filename of the output PDF file.
    // msgs: An optional array in which to return error and warning messages.
    // Returns true if a PDF file was generated successfully.
    public function convert_file_to_file($xmlPath, $pdfPath, &$msgs = array())
    {
	$pathAndArgs = $this->getCommandLine();
	$pathAndArgs .= '"' . $xmlPath . '" "' . $pdfPath . '"';

        return $this->convert_internal_file_to_file($pathAndArgs, $msgs);
    }

    // Convert an XML or HTML string to a PDF file, which will be passed
    // through to the output of the current PHP page.
    // xmlString: A string containing an XML or HTML document.
    // Returns true if a PDF file was generated successfully.
    public function convert_string_to_passthru($xmlString)
    {
	$pathAndArgs = $this->getCommandLine();
	$pathAndArgs .= '--silent -';

        return $this->convert_internal_string_to_passthru($pathAndArgs, $xmlString);
    }

    // Convert an XML or HTML string to a PDF file.
    // xmlString: A string containing an XML or HTML document.
    // pdfPath: The filename of the output PDF file.
    // msgs: An optional array in which to return error and warning messages.
    // Returns true if a PDF file was generated successfully.
    public function convert_string_to_file($xmlString, $pdfPath, &$msgs = array())
    {
	$pathAndArgs = $this->getCommandLine();
	$pathAndArgs .= ' - -o "' . $pdfPath . '"';

        return $this->convert_internal_string_to_file($pathAndArgs, $xmlString, $msgs);
    }

    // Old name for backwards compatibility
    public function convert1($xmlPath, &$msgs = array())
    {
	return $this->convert_file($xmlPath, $msgs);
    }

    // Old name for backwards compatibility
    public function convert2($xmlPath, $pdfPath, &$msgs = array())
    {
	return $this->convert_file_to_file($xmlPath, $pdfPath, $msgs);
    }

    // Old name for backwards compatibility
    public function convert3($xmlString)
    {
	return $this->convert_string_to_passthru($xmlString);
    }

    private function getCommandLine()
    {
	$cmdline = $this->exePath . ' --server ' . $this->styleSheets;

	if ($this->isHTML)
	{
	    $cmdline .= '--input=html ';
	}

	if ($this->baseURL != '')
	{
	    $cmdline .= '--baseurl="' . $this->baseURL . '" ';
	}

	if ($this->doXInclude == false)
	{
	    $cmdline .= '--no-xinclude ';
	}

	if ($this->httpUser != '')
	{
	    $cmdline .= '--http-user="' . $this->httpUser . '" ';
	}

	if ($this->httpPassword != '')
	{
	    $cmdline .= '--http-password="' . $this->httpPassword . '" ';
	}

	if ($this->logFile != '')
	{
	    $cmdline .= '--log="' . $this->logFile . '" ';
	}

	if ($this->embedFonts == false)
	{
	    $cmdline .= '--no-embed-fonts ';
	}

	if ($this->compress == false)
	{
	    $cmdline .= '--no-compress ';
	}

	if ($this->encrypt)
	{
	    $cmdline .= '--encrypt ' . $this->encryptInfo;
	}

	return $cmdline;
    }

    private function convert_internal_file_to_file($pathAndArgs, &$msgs)
    {
	$descriptorspec = array(
				0 => array("pipe", "r"),
				1 => array("pipe", "w"),
				2 => array("pipe", "w")
				);

	$process = proc_open($pathAndArgs, $descriptorspec, $pipes);

	if (is_resource($process))
	{
	    $result = $this->readMessages($pipes[2], $msgs);

	    fclose($pipes[0]);
	    fclose($pipes[1]);
	    fclose($pipes[2]);

	    proc_close($process);

	    return ($result == 'success');
	}
	else
	{
	    throw new Exception("Failed to execute $pathAndArgs");
	}
    }

    private function convert_internal_string_to_file($pathAndArgs, $xmlString, &$msgs)
    {
	$descriptorspec = array(
			    0 => array("pipe", "r"),
			    1 => array("pipe", "w"),
			    2 => array("pipe", "w")
			    );

	$process = proc_open($pathAndArgs, $descriptorspec, $pipes);

	if (is_resource($process))
	{
	    fwrite($pipes[0], $xmlString);
	    fclose($pipes[0]);
	    fclose($pipes[1]);

	    $result = $this->readMessages($pipes[2], $msgs);

	    fclose($pipes[2]);

	    proc_close($process);

	    return ($result == 'success');
	}
	else
	{
	    throw new Exception("Failed to execute $pathAndArgs");
	}
    }

    private function convert_internal_string_to_passthru($pathAndArgs, $xmlString)
    {
	$descriptorspec = array(
			    0 => array("pipe", "r"),
			    1 => array("pipe", "w"),
			    2 => array("pipe", "w")
			    );

	$process = proc_open($pathAndArgs, $descriptorspec, $pipes);

	if (is_resource($process))
	{
	    fwrite($pipes[0], $xmlString);
	    fclose($pipes[0]);
	    fpassthru($pipes[1]);
	    fclose($pipes[1]);

	    $result = $this->readMessages($pipes[2], $msgs);

	    fclose($pipes[2]);

	    proc_close($process);

	    return ($result == 'success');
	}
	else
	{
	    throw new Exception("Failed to execute $pathAndArgs");
	}
    }

    private function readMessages($pipe, &$msgs)
    {
	while (!feof($pipe))
	{
	    $line = fgets($pipe);

	    if ($line != false)
	    {
		$msgtag = substr($line, 0, 4);
		$msgbody = rtrim(substr($line, 4));

		if ($msgtag == 'fin|')
		{
		    return $msgbody;
		}
		else if ($msgtag == 'msg|')
		{
		    $msg = explode('|', $msgbody, 4);

		    // $msg[0] = 'err' | 'wrn' | 'inf'
		    // $msg[1] = filename / line number
		    // $msg[2] = message text, trailing newline stripped

		    $msgs[] = $msg;
		}
		else
		{
		    // ignore other messages
		}
	    }
	}

	return '';
    }
}