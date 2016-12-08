<?php
namespace Brainexploded;

use Brainexploded\FSTools\FSTraverser;

class PhpArrayModernizer
{
    protected $searchSeq = [
        '(//).*?$',
        '/(\*).*?\*/',
        '(")[^"\\\\]*(?:\\\\.[^"\\\\]*)*"',
        "(')[^'\\\\]*(?:\\\\.[^'\\\\]*)*'",
        '\b(array)\s*\(',
        '(\()',
        '(\))'
    ];

    public function modernize($path)
    {
        $tr = new FSTraverser(
            // root dir
            $path,
            // callback
            function($path, $entry, $content) {
                $fullpath = $path.'/'.$entry;

                $handle_write = fopen($fullpath, 'wb');
                fwrite($handle_write, $this->process($content));
                fclose($handle_write);
            },
            // exclude nodes
            ['.git'],
            // allowed extensions
            ['php']
        );
        $tr->go(true);
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


