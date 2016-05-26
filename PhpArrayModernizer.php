<?php

class PhpArrayModernizer
{
    protected $searchSeq = [
        '(//).*?$',
        '/(\*).*?\*/',
        '(").*?"',
        '(\').*?\'',
        '\b(array)\s*\(',
        '(\()',
        '(\))'
    ];
    
    public function isExtension($filename, $extension)
    {
        if (substr($filename, -(strlen($extension)+1)) == ".$extension") {
            return true;
        }
        
        return false;
    }
    
    public function traverse($path)
    {
        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {

                    if (is_dir($path.'/'.$entry)) {
                        $this->traverse($path.'/'.$entry);
                    } else {
                        $this->parseFile($path.'/'.$entry);
                    }
                }
            }
        }
    }
    
    public function parseFile($filePath)
    {
        if (!file_exists($filePath)) {
        return;
        }
        if ($this->isExtension($filePath, 'php')) {
            $handle = fopen($filePath, 'rb');
            if ($handle === false) {
                echo "[ERROR] cannot open file $filePath", PHP_EOL;
                return;
            }
            $size = filesize($filePath);
            if ($size > 0) {
                $content = fread($handle, filesize($filePath));
                
                $handle_write = fopen($filePath, 'wb');
                fwrite($handle_write, $this->process($content));
                fclose($handle_write);
                
                unset($content);
            } else {
                echo "[NOTICE] file $filePath is empty!", PHP_EOL;
            }
            fclose($handle);
        }
    }
    
    protected function process($data)
    {
        $offset = 0;
        $pattern = '~(?|' . implode($this->searchSeq, '|') . ')~mus';
        $pairs = [];
        
        while (preg_match($pattern, $data, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            if ($matches) {
                if (count($matches) > 1) {
                    switch ($matches[1][0]) {
                        case 'array':
                        case '(':
                            $pairs[] = $matches[1][0];
                            if ($matches[1][0] == 'array') {
                                $data = substr_replace($data, '[', $matches[0][1], strlen($matches[0][0]));
                            }
                            $offset = $matches[0][1] + 1;
                            break;
                        case ')':
                            if (array_pop($pairs) == 'array') {
                                $data = substr_replace($data, ']', $matches[0][1], strlen($matches[0][0]));
                            }
                            $offset = $matches[0][1] + 1;
                            break;
                        default:
                            $offset = $matches[0][1] + strlen($matches[0][0]);
                    }
                } else {
                    $offset = $matches[0][1] + strlen($matches[0][0]);
                }
            }
        }

        return $data;
    }
}


