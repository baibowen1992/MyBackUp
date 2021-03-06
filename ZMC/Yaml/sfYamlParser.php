<?








if (!defined('PREG_BAD_UTF8_OFFSET_ERROR'))
{
  define('PREG_BAD_UTF8_OFFSET_ERROR', 5);
}









class ZMC_Yaml_sfYamlParser
{
  protected
    $offset        = 0,
    $lines         = array(),
    $currentLineNb = -1,
    $currentLine   = '',
    $refs          = array();

  




  public function __construct($offset = 0)
  {
    $this->offset = $offset;
  }

  








  public function parse($value)
  {
    $value = str_replace("\t", '  ', $value); 

    $this->currentLineNb = -1;
    $this->currentLine = '';
    $this->lines = explode("\n", $this->cleanup($value));

    if (function_exists('mb_internal_encoding') && ((int) ini_get('mbstring.func_overload')) & 2)
    {
      $mbEncoding = mb_internal_encoding();
      mb_internal_encoding('ASCII');
    }

    $data = array();
    while ($this->moveToNextLine())
    {
      if ($this->isCurrentLineEmpty())
      {
        continue;
      }

      
      if (preg_match('#^\t+#', $this->currentLine))
      {
        throw new InvalidArgumentException(sprintf('A YAML file cannot contain tabs as indentation at line %d (%s).', $this->getRealCurrentLineNb() + 1, $this->currentLine));
      }

      $isRef = $isInPlace = $isProcessed = false;
      if (preg_match('#^\-((?P<leadspaces>\s+)(?P<value>.+?))?\s*$#', $this->currentLine, $values))
      {
        if (isset($values['value']) && preg_match('#^&(?P<ref>[^ ]+) *(?P<value>.*)#', $values['value'], $matches))
        {
          $isRef = $matches['ref'];
          $values['value'] = $matches['value'];
        }

        
        if (!isset($values['value']) || '' == trim($values['value'], ' ') || 0 === strpos(ltrim($values['value'], ' '), '#'))
        {
          $c = $this->getRealCurrentLineNb() + 1;
          $parser = new self($c);
          $parser->refs =& $this->refs;
          $data[] = $parser->parse($this->getNextEmbedBlock());
        }
        else
        {
          if (isset($values['leadspaces'])
            && ' ' == $values['leadspaces']
            && preg_match('#^(?P<key>'.ZMC_Yaml_sfYamlInline::REGEX_QUOTED_STRING.'|[^ \'"\{].*?) *\:(\s+(?P<value>.+?))?\s*$#', $values['value'], $matches))
          {
            
            $c = $this->getRealCurrentLineNb();
            $parser = new self($c);
            $parser->refs =& $this->refs;

            $block = $values['value'];
            if (!$this->isNextLineIndented())
            {
              $block .= "\n".$this->getNextEmbedBlock($this->getCurrentLineIndentation() + 2);
            }

            $data[] = $parser->parse($block);
          }
          else
          {
            $data[] = $this->parseValue($values['value']);
          }
        }
      }
      else if (preg_match('#^(?P<key>'.ZMC_Yaml_sfYamlInline::REGEX_QUOTED_STRING.'|[^ \'"].*?) *\:(\s+(?P<value>.+?))?\s*$#', $this->currentLine, $values))
      {
        $key = ZMC_Yaml_sfYamlInline::parseScalar($values['key']);

        if ('<<' === $key)
        {
          if (isset($values['value']) && '*' === substr($values['value'], 0, 1))
          {
            $isInPlace = substr($values['value'], 1);
            if (!array_key_exists($isInPlace, $this->refs))
            {
              throw new InvalidArgumentException(sprintf('Reference "%s" does not exist at line %s (%s).', $isInPlace, $this->getRealCurrentLineNb() + 1, $this->currentLine));
            }
          }
          else
          {
            if (isset($values['value']) && $values['value'] !== '')
            {
              $value = $values['value'];
            }
            else
            {
              $value = $this->getNextEmbedBlock();
            }
            $c = $this->getRealCurrentLineNb() + 1;
            $parser = new self($c);
            $parser->refs =& $this->refs;
            $parsed = $parser->parse($value);

            $merged = array();
            if (!is_array($parsed))
            {
              throw new InvalidArgumentException(sprintf("YAML merge keys used with a scalar value instead of an array at line %s (%s)", $this->getRealCurrentLineNb() + 1, $this->currentLine));
            }
            else if (isset($parsed[0]))
            {
              
              foreach (array_reverse($parsed) as $parsedItem)
              {
                if (!is_array($parsedItem))
                {
                  throw new InvalidArgumentException(sprintf("Merge items must be arrays at line %s (%s).", $this->getRealCurrentLineNb() + 1, $parsedItem));
                }
                $merged = array_merge($parsedItem, $merged);
              }
            }
            else
            {
              
              $merged = array_merge($merge, $parsed);
            }

            $isProcessed = $merged;
          }
        }
        else if (isset($values['value']) && preg_match('#^&(?P<ref>[^ ]+) *(?P<value>.*)#', $values['value'], $matches))
        {
          $isRef = $matches['ref'];
          $values['value'] = $matches['value'];
        }

        if ($isProcessed)
        {
          
          $data = $isProcessed;
        }
        
        else if (!isset($values['value']) || '' == trim($values['value'], ' ') || 0 === strpos(ltrim($values['value'], ' '), '#'))
        {
          
          if ($this->isNextLineIndented())
          {
            $data[$key] = null;
          }
          else
          {
            $c = $this->getRealCurrentLineNb() + 1;
            $parser = new self($c);
            $parser->refs =& $this->refs;
            $data[$key] = $parser->parse($this->getNextEmbedBlock());
          }
        }
        else
        {
          if ($isInPlace)
          {
            $data = $this->refs[$isInPlace];
          }
          else
          {
            $data[$key] = $this->parseValue($values['value']);
          }
        }
      }
      else
      {
        
        if (2 == count($this->lines) && empty($this->lines[1]))
        {
          $value = ZMC_Yaml_sfYamlInline::load($this->lines[0]);
          if (is_array($value))
          {
            $first = reset($value);
            if ('*' === substr($first, 0, 1))
            {
              $data = array();
              foreach ($value as $alias)
              {
                $data[] = $this->refs[substr($alias, 1)];
              }
              $value = $data;
            }
          }

          if (isset($mbEncoding))
          {
            mb_internal_encoding($mbEncoding);
          }

          return $value;
        }

        switch (preg_last_error())
        {
          case PREG_INTERNAL_ERROR:
            $error = 'Internal PCRE error on line';
            break;
          case PREG_BACKTRACK_LIMIT_ERROR:
            $error = 'pcre.backtrack_limit reached on line';
            break;
          case PREG_RECURSION_LIMIT_ERROR:
            $error = 'pcre.recursion_limit reached on line';
            break;
          case PREG_BAD_UTF8_ERROR:
            $error = 'Malformed UTF-8 data on line';
            break;
          case PREG_BAD_UTF8_OFFSET_ERROR:
            $error = 'Offset doesn\'t correspond to the begin of a valid UTF-8 code point on line';
            break;
          default:
            $error = 'Unable to parse line';
        }

        throw new InvalidArgumentException(sprintf('%s %d (%s).', $error, $this->getRealCurrentLineNb() + 1, $this->currentLine));
      }

      if ($isRef)
      {
        $this->refs[$isRef] = end($data);
      }
    }

    if (isset($mbEncoding))
    {
      mb_internal_encoding($mbEncoding);
    }

    return empty($data) ? null : $data;
  }

  




  protected function getRealCurrentLineNb()
  {
    return $this->currentLineNb + $this->offset;
  }

  




  protected function getCurrentLineIndentation()
  {
    return strlen($this->currentLine) - strlen(ltrim($this->currentLine, ' '));
  }

  






  protected function getNextEmbedBlock($indentation = null)
  {
    $this->moveToNextLine();

    if (null === $indentation)
    {
      $newIndent = $this->getCurrentLineIndentation();

      if (!$this->isCurrentLineEmpty() && 0 == $newIndent)
      {
        throw new InvalidArgumentException(sprintf('Indentation problem at line %d (%s)', $this->getRealCurrentLineNb() + 1, $this->currentLine));
      }
    }
    else
    {
      $newIndent = $indentation;
    }

    $data = array(substr($this->currentLine, $newIndent));

    while ($this->moveToNextLine())
    {
      if ($this->isCurrentLineEmpty())
      {
        if ($this->isCurrentLineBlank())
        {
          $data[] = substr($this->currentLine, $newIndent);
        }

        continue;
      }

      $indent = $this->getCurrentLineIndentation();

      if (preg_match('#^(?P<text> *)$#', $this->currentLine, $match))
      {
        
        $data[] = $match['text'];
      }
      else if ($indent >= $newIndent)
      {
        $data[] = substr($this->currentLine, $newIndent);
      }
      else if (0 == $indent)
      {
        $this->moveToPreviousLine();

        break;
      }
      else
      {
        throw new InvalidArgumentException(sprintf('Indentation problem at line %d (%s)', $this->getRealCurrentLineNb() + 1, $this->currentLine));
      }
    }

    return implode("\n", $data);
  }

  


  protected function moveToNextLine()
  {
    if ($this->currentLineNb >= count($this->lines) - 1)
    {
      return false;
    }

    $this->currentLine = $this->lines[++$this->currentLineNb];

    return true;
  }

  


  protected function moveToPreviousLine()
  {
    $this->currentLine = $this->lines[--$this->currentLineNb];
  }

  






  protected function parseValue($value)
  {
    if ('*' === substr($value, 0, 1))
    {
      if (false !== $pos = strpos($value, '#'))
      {
        $value = substr($value, 1, $pos - 2);
      }
      else
      {
        $value = substr($value, 1);
      }

      if (!array_key_exists($value, $this->refs))
      {
        throw new InvalidArgumentException(sprintf('Reference "%s" does not exist (%s).', $value, $this->currentLine));
      }
      return $this->refs[$value];
    }

    if (preg_match('/^(?P<separator>\||>)(?P<modifiers>\+|\-|\d+|\+\d+|\-\d+|\d+\+|\d+\-)?(?P<comments> +#.*)?$/', $value, $matches))
    {
      $modifiers = isset($matches['modifiers']) ? $matches['modifiers'] : '';

      return $this->parseFoldedScalar($matches['separator'], preg_replace('#\d+#', '', $modifiers), intval(abs($modifiers)));
    }
    else
    {
      return ZMC_Yaml_sfYamlInline::load($value);
    }
  }

  








  protected function parseFoldedScalar($separator, $indicator = '', $indentation = 0)
  {
    $separator = '|' == $separator ? "\n" : ' ';
    $text = '';

    $notEOF = $this->moveToNextLine();

    while ($notEOF && $this->isCurrentLineBlank())
    {
      $text .= "\n";

      $notEOF = $this->moveToNextLine();
    }

    if (!$notEOF)
    {
      return '';
    }

    if (!preg_match('#^(?P<indent>'.($indentation ? str_repeat(' ', $indentation) : ' +').')(?P<text>.*)$#', $this->currentLine, $matches))
    {
      $this->moveToPreviousLine();

      return '';
    }

    $textIndent = $matches['indent'];
    $previousIndent = 0;

    $text .= $matches['text'].$separator;
    while ($this->currentLineNb + 1 < count($this->lines))
    {
      $this->moveToNextLine();

      if (preg_match('#^(?P<indent> {'.strlen($textIndent).',})(?P<text>.+)$#', $this->currentLine, $matches))
      {
        if (' ' == $separator && $previousIndent != $matches['indent'])
        {
          $text = substr($text, 0, -1)."\n";
        }
        $previousIndent = $matches['indent'];

        $text .= str_repeat(' ', $diff = strlen($matches['indent']) - strlen($textIndent)).$matches['text'].($diff ? "\n" : $separator);
      }
      else if (preg_match('#^(?P<text> *)$#', $this->currentLine, $matches))
      {
        $text .= preg_replace('#^ {1,'.strlen($textIndent).'}#', '', $matches['text'])."\n";
      }
      else
      {
        $this->moveToPreviousLine();

        break;
      }
    }

    if (' ' == $separator)
    {
      
      $text = preg_replace('/ (\n*)$/', "\n$1", $text);
    }

    switch ($indicator)
    {
      case '':
        $text = preg_replace('#\n+$#s', "\n", $text);
        break;
      case '+':
        break;
      case '-':
        $text = preg_replace('#\n+$#s', '', $text);
        break;
    }

    return $text;
  }

  




  protected function isNextLineIndented()
  {
    $currentIndentation = $this->getCurrentLineIndentation();
    $notEOF = $this->moveToNextLine();

    while ($notEOF && $this->isCurrentLineEmpty())
    {
      $notEOF = $this->moveToNextLine();
    }

    if (false === $notEOF)
    {
      return false;
    }

    $ret = false;
    if ($this->getCurrentLineIndentation() <= $currentIndentation)
    {
      $ret = true;
    }

    $this->moveToPreviousLine();

    return $ret;
  }

  




  protected function isCurrentLineEmpty()
  {
    return $this->isCurrentLineBlank() || $this->isCurrentLineComment();
  }

  




  protected function isCurrentLineBlank()
  {
    return '' == trim($this->currentLine, ' ');
  }

  




  protected function isCurrentLineComment()
  {
    
    $ltrimmedLine = ltrim($this->currentLine, ' ');
    return $ltrimmedLine[0] === '#';
  }

  






  protected function cleanup($value)
  {
    $value = str_replace(array("\r\n", "\r"), "\n", $value);

    if (!preg_match("#\n$#", $value))
    {
      $value .= "\n";
    }

    
    $count = 0;
    $value = preg_replace('#^\%YAML[: ][\d\.]+.*\n#s', '', $value, -1, $count);
    $this->offset += $count;

    
    $trimmedValue = preg_replace('#^((\#.*?\n)|(\-\-\-.*?\n))*#s', '', $value, -1, $count);
    if ($count == 1)
    {
      
      $this->offset += substr_count($value, "\n") - substr_count($trimmedValue, "\n");
      $value = $trimmedValue;
    }

    return $value;
  }
}
