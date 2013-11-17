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

/*
 * Prince - PHP interface (prince-php5-r13)
 * Copyright 2005-2013 YesLogic Pty. Ltd.
 * http://www.princexml.com
 *
 * @see http://www.princexml.com/download/wrappers/
 */
class Prince
{
    private $exePath;
    private $styleSheets     = '';
    private $scripts         = '';
    private $fileAttachments = '';
    private $licenseFile     = '';
    private $licenseKey      = '';
    private $inputType       = 'auto';
    private $javascript      = false;
    private $baseURL         = '';
    private $doXInclude      = true;
    private $httpUser        = '';
    private $httpPassword    = '';
    private $httpProxy       = '';
    private $insecure        = false;
    private $logFile         = '';
    private $fileRoot        = '';
    private $embedFonts      = true;
    private $subsetFonts     = true;
    private $artificialFonts = true;
    private $compress        = true;
    private $pdfTitle        = '';
    private $pdfSubject      = '';
    private $pdfAuthor       = '';
    private $pdfKeywords     = '';
    private $pdfCreator      = '';
    private $encrypt         = false;
    private $encryptInfo     = '';

    public function __construct($exePath)
    {
        $this->exePath = $this->addDoubleQuotes(ltrim($exePath));
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

    // Add a JavaScript script that will be run before conversion.
    // jsPath: The filename of the script.
    public function addScript($jsPath)
    {
        $this->scripts .= '--script "' . $jsPath . '" ';
    }

    // Clear all of the scripts.
    public function clearScripts()
    {
        $this->scripts = '';
    }

    // Add a file attachment that will be attached to the PDF file
    // filePath: The filename of the file attachment.
    public function addFileAttachment($filePath)
    {
        $this->fileAttachments .= '--attach=' . '"' . $filePath . '" ';
    }

    // Clear all of the file attachments.
    public function clearFileAttachments()
    {
        $this->fileAttachments = '';
    }

    // Specify the license file.
    // file: The filename of the license file.
    public function setLicenseFile($file)
    {
        $this->licenseFile = $file;
    }

    // Specify the license key.
    // key: The license key
    public function setLicenseKey($key)
    {
        $this->licenseKey = $key;
    }

    // Specify the input type of the document.
    // inputType: Can take a value of : "xml", "html" or "auto".
    public function setInputType($inputType)
    {
        $this->inputType = $inputType;
    }

    // Specify whether JavaScript found in documents should be run. 
    // js: True if document scripts should be run.
    public function setJavaScript($js)
    {
        $this->javascript = $js;
    }

    // Specify whether documents should be parsed as HTML or XML/XHTML.
    // html: True if all documents should be treated as HTML.
    public function setHTML($html)
    {
        if ($html) {
            $this->inputType = "html";
        } else {
            $this->inputType = "xml";
        }
    }

    // Specify a file that Prince should use to log error/warning messages.
    // logFile: The filename that Prince should use to log error/warning
    //        messages, or '' to disable logging.
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
        $this->httpUser = $this->cmdlineArgEscape($user);
    }

    // Specify a password to use when fetching remote resources over HTTP.
    // password: The password to use for basic HTTP authentication.
    public function setHttpPassword($password)
    {
        $this->httpPassword = $this->cmdlineArgEscape($password);
    }

    // Specify the URL for the HTTP proxy server, if needed.
    // proxy: The URL for the HTTP proxy server.
    public function setHttpProxy($proxy)
    {
        $this->httpProxy = $proxy;
    }

    // Specify whether to disable SSL verification.
    // insecure: If set to true, SSL verification is disabled. (not recommended)
    public function setInsecure($insecure)
    {
        $this->insecure = $insecure;
    }

    // Specify the root directory for absolute filenames. This can be used
    // when converting a local file that uses absolute paths to refer to web
    // resources. For example, /images/logo.jpg can be 
    // rewritten to /usr/share/images/logo.jpg by specifying "/usr/share" as the root.
    // fileRoot: The path to prepend to absolute filenames.
    public function setFileRoot($fileRoot)
    {
        $this->fileRoot = $fileRoot;
    }

    // Specify whether fonts should be embedded in the output PDF file. Fonts
    // will be embedded by default unless explicitly disabled.
    // embedFonts: False to disable PDF font embedding.
    public function setEmbedFonts($embedFonts)
    {
        $this->embedFonts = $embedFonts;
    }

    // Specify whether embedded fonts should be subset.
    // Fonts will be subset by default unless explicitly disabled.
    // subsetFonts: False to disable PDF font subsetting.
    public function setSubsetFonts($subsetFonts)
    {
        $this->subsetFonts = $subsetFonts;
    }

    // Specify whether artificial bold/italic fonts should be generated if
    // necessary. Artificial fonts are enabled by default.
    // artificialFonts: False to disable artificial bold/italic fonts.
    public function setArtificialFonts($artificialFonts)
    {
        $this->artificialFonts = $artificialFonts;
    }

    // Specify whether compression should be applied to the output PDF file.
    // Compression will be applied by default unless explicitly disabled.
    // compress: False to disable PDF compression.
    public function setCompress($compress)
    {
        $this->compress = $compress;
    }

    // Specify the document title for PDF metadata.
    public function setPDFTitle($pdfTitle)
    {
        $this->pdfTitle = $pdfTitle;
    }

    // Specify the document subject for PDF metadata.
    public function setPDFSubject($pdfSubject)
    {
        $this->pdfSubject = $pdfSubject;
    }

    // Specify the document author for PDF metadata.
    public function setPDFAuthor($pdfAuthor)
    {
        $this->pdfAuthor = $pdfAuthor;
    }

    // Specify the document keywords for PDF metadata.
    public function setPDFKeywords($pdfKeywords)
    {
        $this->pdfKeywords = $pdfKeywords;
    }

    // Specify the document creator for PDF metadata.
    public function setPDFCreator($pdfCreator)
    {
        $this->pdfCreator = $pdfCreator;
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
    public function setEncryptInfo($keyBits, $userPassword, $ownerPassword, $disallowPrint = false, $disallowModify = false, $disallowCopy = false, $disallowAnnotate = false) {
        if ($keyBits != 40 && $keyBits != 128) {
            throw new \Exception("Invalid value for keyBits: $keyBits" .
                " (must be 40 or 128)");
        }

        $this->encrypt = true;

        $this->encryptInfo =
            ' --key-bits ' . $keyBits .
            ' --user-password="' . $this->cmdlineArgEscape($userPassword) .
            '" --owner-password="' . $this->cmdlineArgEscape($ownerPassword) . '" ';

        if ($disallowPrint) {
            $this->encryptInfo .= '--disallow-print ';
        }

        if ($disallowModify) {
            $this->encryptInfo .= '--disallow-modify ';
        }

        if ($disallowCopy) {
            $this->encryptInfo .= '--disallow-copy ';
        }

        if ($disallowAnnotate) {
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
        $pathAndArgs .= '"' . $xmlPath . '" -o "' . $pdfPath . '"';

        return $this->convert_internal_file_to_file($pathAndArgs, $msgs);
    }

    // Convert multiple XML or HTML files to a PDF file.
    // xmlPaths: An array of the input XML or HTML documents.
    // msgs: An optional array in which to return error and warning messages.
    // Returns true if a PDF file was generated successfully.
    public function convert_multiple_files($xmlPaths, $pdfPath, &$msgs = array())
    {
        $pathAndArgs = $this->getCommandLine();

        foreach ($xmlPaths as $xmlPath) {
            $pathAndArgs .= '"' . $xmlPath . '" ';
        }
        $pathAndArgs .= '-o "' . $pdfPath . '"';

        return $this->convert_internal_file_to_file($pathAndArgs, $msgs);
    }

    // Convert multiple XML or HTML files to a PDF file, which will be passed
    // through to the output buffer of the current PHP page.
    // xmlPaths: An array of the input XML or HTML documents.
    // Returns true if a PDF file was generated successfully.
    public function convert_multiple_files_to_passthru($xmlPaths)
    {
        $pathAndArgs = $this->getCommandLine();
        $pathAndArgs .= '--silent ';

        foreach ($xmlPaths as $xmlPath) {
            $pathAndArgs .= '"' . $xmlPath . '" ';
        }
        $pathAndArgs .= '-o -';

        return $this->convert_internal_file_to_passthru($pathAndArgs);
    }

    // Convert an XML or HTML file to a PDF file, which will be passed
    // through to the output buffer of the current PHP page.
    // xmlPath: The filename of the input XML or HTML document.
    // Returns true if a PDF file was generated successfully.
    public function convert_file_to_passthru($xmlPath)
    {
        $pathAndArgs = $this->getCommandLine();
        $pathAndArgs .= '--silent "' . $xmlPath . '" -o -';

        return $this->convert_internal_file_to_passthru($pathAndArgs);
    }

    // Convert an XML or HTML string to a PDF file, which will be passed
    // through to the output buffer of the current PHP page.
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
        $cmdline = $this->exePath . ' --server ' . $this->styleSheets . $this->scripts . $this->fileAttachments;

        if ($this->inputType == "auto") {
        } else {
            $cmdline .= '-i "' . $this->inputType . '" ';
        }

        if ($this->javascript) {
            $cmdline .= '--javascript ';
        }

        if ($this->baseURL != '') {
            $cmdline .= '--baseurl="' . $this->baseURL . '" ';
        }

        if ($this->doXInclude == false) {
            $cmdline .= '--no-xinclude ';
        }

        if ($this->httpUser != '') {
            $cmdline .= '--http-user="' . $this->httpUser . '" ';
        }

        if ($this->httpPassword != '') {
            $cmdline .= '--http-password="' . $this->httpPassword . '" ';
        }

        if ($this->httpProxy != '') {
            $cmdline .= '--http-proxy="' . $this->httpProxy . '" ';
        }

        if ($this->insecure) {
            $cmdline .= '--insecure ';
        }

        if ($this->logFile != '') {
            $cmdline .= '--log="' . $this->logFile . '" ';
        }

        if ($this->fileRoot != '') {
            $cmdline .= '--fileroot="' . $this->fileRoot . '" ';
        }

        if ($this->licenseFile != '') {
            $cmdline .= '--license-file="' . $this->licenseFile . '" ';
        }

        if ($this->licenseKey != '') {
            $cmdline .= '--license-key="' . $this->licenseKey . '" ';
        }

        if ($this->embedFonts == false) {
            $cmdline .= '--no-embed-fonts ';
        }

        if ($this->subsetFonts == false) {
            $cmdline .= '--no-subset-fonts ';
        }

        if ($this->artificialFonts == false) {
            $cmdline .= '--no-artificial-fonts ';
        }

        if ($this->compress == false) {
            $cmdline .= '--no-compress ';
        }

        if ($this->pdfTitle != '') {
            $cmdline .= '--pdf-title="' . $this->cmdlineArgEscape($this->pdfTitle) . '" ';
        }

        if ($this->pdfSubject != '') {
            $cmdline .= '--pdf-subject="' . $this->cmdlineArgEscape($this->pdfSubject) . '" ';
        }

        if ($this->pdfAuthor != '') {
            $cmdline .= '--pdf-author="' . $this->cmdlineArgEscape($this->pdfAuthor) . '" ';
        }

        if ($this->pdfKeywords != '') {
            $cmdline .= '--pdf-keywords="' . $this->cmdlineArgEscape($this->pdfKeywords) . '" ';
        }

        if ($this->pdfCreator != '') {
            $cmdline .= '--pdf-creator="' . $this->cmdlineArgEscape($this->pdfCreator) . '" ';
        }

        if ($this->encrypt) {
            $cmdline .= '--encrypt ' . $this->encryptInfo;
        }

        return $cmdline;
    }


    private function convert_internal_file_to_file($pathAndArgs, &$msgs)
    {
        $descriptorspec = $this->getDescriptors();

        $process = proc_open($pathAndArgs, $descriptorspec, $pipes);

        if (is_resource($process)) {
            $result = $this->readMessages($pipes[2], $msgs);

            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            proc_close($process);

            return ($result == 'success');
        } else {
            throw new \Exception("Failed to execute $pathAndArgs");
        }
    }

    private function convert_internal_string_to_file($pathAndArgs, $xmlString, &$msgs)
    {
        $descriptorspec = $this->getDescriptors();

        $process = proc_open($pathAndArgs, $descriptorspec, $pipes);

        if (is_resource($process)) {
            fwrite($pipes[0], $xmlString);
            fclose($pipes[0]);
            fclose($pipes[1]);

            $result = $this->readMessages($pipes[2], $msgs);

            fclose($pipes[2]);

            proc_close($process);

            return ($result == 'success');
        } else {
            throw new \Exception("Failed to execute $pathAndArgs");
        }
    }

    private function convert_internal_file_to_passthru($pathAndArgs)
    {
        $descriptorspec = $this->getDescriptors();

        $process = proc_open($pathAndArgs, $descriptorspec, $pipes);

        if (is_resource($process)) {
            fclose($pipes[0]);
            fpassthru($pipes[1]);
            fclose($pipes[1]);

            $result = $this->readMessages($pipes[2], $msgs);

            fclose($pipes[2]);

            proc_close($process);

            return ($result == 'success');
        } else {
            throw new \Exception("Failed to execute $pathAndArgs");
        }
    }

    private function convert_internal_string_to_passthru($pathAndArgs, $xmlString)
    {
        $descriptorspec = $this->getDescriptors();

        $process = proc_open($pathAndArgs, $descriptorspec, $pipes);

        if (is_resource($process)) {
            fwrite($pipes[0], $xmlString);
            fclose($pipes[0]);
            fpassthru($pipes[1]);
            fclose($pipes[1]);

            $result = $this->readMessages($pipes[2], $msgs);

            fclose($pipes[2]);

            proc_close($process);

            return ($result == 'success');
        } else {
            throw new \Exception("Failed to execute $pathAndArgs");
        }
    }

    // $msgs = array(0 => $msg, ...);
    //   $msg[0] = 'err' | 'wrn' | 'inf'
    //   $msg[1] = filename / line number
    //   $msg[2] = message text, trailing newline stripped
    private function readMessages($pipe, &$msgs)
    {
        while (!feof($pipe)) {
            $line = fgets($pipe);

            if ($line != false) {
                $msgtag = substr($line, 0, 4);
                $msgbody = rtrim(substr($line, 4));

                if ($msgtag == 'fin|') {
                    return $msgbody;
                } else {
                    if ($msgtag == 'msg|') {
                        $msg = explode('|', $msgbody, 4);
                        $msgs[] = $msg;
                    } else {
                        // ignore other messages
                    }
                }
            }
        }

        return '';
    }


    // Puts double-quotes around space(s) in file path,
    // and also around semicolon(;), comma(,), ampersand(&), up-arrow(^) and parentheses.
    // This is needed if the file path is used in a command line.
    private function addDoubleQuotes($str)
    {
        $len = strlen($str);

        $outputStr = '';
        $numWeirdChars = 0;
        $subStrStart = 0;
        for ($i = 0; $i < $len; $i++) {
            if (in_array($str[$i], array(' ', ';', ',', '&', '^', '(', ')'))) {
                if ($numWeirdChars == 0) {
                    $outputStr .= substr($str, $subStrStart, ($i - $subStrStart));
                    $weirdCharsStart = $i;
                }
                $numWeirdChars += 1;
            } else {
                if ($numWeirdChars > 0) {
                    $outputStr .= chr(34) . substr($str, $weirdCharsStart, $numWeirdChars) . chr(34);

                    $subStrStart = $i;
                    $numWeirdChars = 0;
                }
            }
        }
        $outputStr .= substr($str, $subStrStart, ($i - $subStrStart));

        return $outputStr;
    }

    private function cmdlineArgEscape($argStr)
    {
        return $this->cmdlineArgEscape2($this->cmdlineArgEscape1($argStr));
    }

    // In the input string $argStr, a double quote with zero or more preceding backslash(es)
    // will be replaced with: n*backslash + doublequote => (2*n+1)*backslash + doublequote
    private function cmdlineArgEscape1($argStr)
    {
        //chr(34) is character double quote ( " ), chr(92) is character backslash ( \ ).
        $len = strlen($argStr);

        $outputStr = '';
        $numSlashes = 0;
        $subStrStart = 0;

        for ($i = 0; $i < $len; $i++) {
            if ($argStr[$i] == chr(34)) {
                $numSlashes = 0;
                $j = $i - 1;
                while ($j >= 0) {
                    if ($argStr[$j] == chr(92)) {
                        $numSlashes += 1;
                        $j -= 1;
                    } else {
                        break;
                    }
                }

                $outputStr .= substr($argStr, $subStrStart, ($i - $numSlashes - $subStrStart));

                for ($k = 0; $k < $numSlashes; $k++) {
                    $outputStr .= chr(92) . chr(92);
                }
                $outputStr .= chr(92) . chr(34);

                $subStrStart = $i + 1;
            }
        }
        $outputStr .= substr($argStr, $subStrStart, ($i - $subStrStart));

        return $outputStr;
    }

    // Double the number of trailing backslash(es):
    // n*trailing backslash => (2*n)*trailing backslash.
    private function cmdlineArgEscape2($argStr)
    {
        //chr(92) is character backslash ( \ ).
        $len = strlen($argStr);

        $numTrailingSlashes = 0;
        for ($i = ($len - 1); $i >= 0; $i--) {
            if ($argStr[$i] == chr(92)) {
                $numTrailingSlashes += 1;
            } else {
                break;
            }
        }

        while ($numTrailingSlashes > 0) {
            $argStr .= chr(92);
            $numTrailingSlashes -= 1;
        }

        return $argStr;
    }

    private function getDescriptors()
    {
        return array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );
    }
}