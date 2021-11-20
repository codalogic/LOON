<?php

//----------------------------------------------------------------------------
// Licensed under the MIT/X11 license - https://opensource.org/licenses/MIT
//----
// Copyright (c) 2021, Codalogic Ltd (www.codalogic.com)
//
// Permission is hereby granted, free of charge, to any person obtaining a
// copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation
// the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the
// Software is furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included
// in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
// THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
// FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
// DEALINGS IN THE SOFTWARE.
//----------------------------------------------------------------------------

//----------------------------------------------------------------------------
// This is a hastily translated version of the Ruby version.
//----------------------------------------------------------------------------

class LOONError
{
    public $message;

    public function __construct( $message )
    {
        $this->message = $message;
    }

    public function error()
    {
        return $this->message;
    }
}

class LOON
{
    private $state;
    private $line_num;
    private $stack;
    private $base;
    private $current;
    private $is_naked;
    private $error_message;
    private $multistring_string;
    private $multistring_end;

    private const STATE_INIT = 0;
    private const STATE_IN_OBJECT = 1;
    private const STATE_IN_ARRAY = 2;
    private const STATE_IN_MULTISTRING = 3;
    private const STATE_COMPLETE = 4;
    private const STATE_ERRORED = 5;

    public function __construct()
    {
        $this->reset();
    }

    public function reset()
    {
        $this->state = self::STATE_INIT;
        $this->line_num = 0;
        $this->stack = array(); // [];
        $this->base = array(); // {};
        $this->current = null;
        $this->is_naked = false;
        $this->error_message = '';
        $this->multistring_string = '';
        $this->multistring_end = '';
    }

    public function parse_line( $line )
    {
        $this->line_num += 1;
        if( $this->state == self::STATE_IN_MULTISTRING )
            $this->when_in_multistring( $line );
        else {
            $line = trim( $line );
            if( $line != "" && $line[0] != '#' ) {
                switch( $this->state ) {
                    case self::STATE_INIT:
                        $this->when_init( $line );
                    break;
                    case self::STATE_IN_OBJECT:
                        $this->when_in_object( $line );
                    break;
                    case self::STATE_IN_ARRAY:
                        $this->when_in_array( $line );
                    break;
                    default:
                        // Do nothing
                        return false;
                }
            }
        }
        return true;
    }

    public function result()
    {
        return $this->base;
    }

    public function is_finished()     // Only applies for non-naked object form
    {
        return $this->state == self::STATE_COMPLETE || $this->state == self::STATE_ERRORED;
    }

    public function is_good()
    {
        return $this->state != self::STATE_ERRORED;
    }

    public function error()
    {
        return $this->error_message;
    }

    private function when_init( $line )
    {
        if( $line == '{' ) {
            $this->base = array();    // {}
            $this->current = & $this->base;    // {}
            $this->state = self::STATE_IN_OBJECT;
        }
        elseif( $line == '[' ) {
            $this->base = array();    // []
            $this->current = & $this->base;    // []
            $this->state = self::STATE_IN_ARRAY;
        }
        else {
            $this->base = array();   // Naked object
            $this->current = & $this->base;
            $this->state = self::STATE_IN_OBJECT;
            $this->is_naked = true;
            $this->when_in_object( $line );
        }
    }

    private const REGEX_MEMBER_SPLIT = '/^((?:\w+\.)*@?\w+)\s*(.*)/';   // *(name ".") ["@"] name

    private function when_in_object( $line )
    {
        if( preg_match( self::REGEX_MEMBER_SPLIT, $line, $m ) > 0 ) {
            $name = $m[1];
            $rhs = $m[2];
            if( isset( $this->current[$name] ) )
                $this->record_error( "Duplicate name ($name) in object" );
            elseif( $rhs == '' )
                $this->current[$name] = null;
            elseif( $rhs[0] == ':' ) {
                $this->current[$name] = $this->parse_inline_string_value( trim( substr( $rhs, 1 ) ) );
            }
            else {
                $my_current =& $this->current;  // parse_value may change $this->current
                $my_current[$name] =& $this->parse_value( $rhs );
            }
        }
        elseif( $line == '}' )
            $this->pop_stack();
        elseif( $line == ']' )
            $this->record_error( "Unexpected array close (\"]\") in object" );
        else
            $this->record_error( "Illegal member format in object: $line" );
    }

    private function when_in_array( $line )
    {
        if( $line == ']' )
            $this->pop_stack();
        elseif( $line == '}' )
            $this->record_error( "Unexpected object close (\"}\") in array" );
        else {
            $my_current = &$this->current;  // parse_value may change $this->current
            $my_current[] =& $this->parse_value( $line );
        }
    }

    private const REGEX_MULITILINE_END = '/(.*)<<\w+$/';

    private function when_in_multistring( $line )
    {
        $test_line = rtrim( $line );
        if( $this->ends_with( $test_line, @multistring_end ) && preg_match( self::REGEX_MULITILINE_END, $test_line, $m ) ) {
            $line = $m[1];
            $this->pop_stack();
        }
        if( $this->multistring_string != '' )
            $this->multistring_string .= "\n";
        $this->multistring_string .= $this->string_unescape( rtrim( $line, "\n\r" ) );    // Remove any newlines but leave other trailing whitespace
    }

    private const REGEX_MULITILINE_START = "/^<<\w+$/";

    private function & parse_value( $value )
    {
        if( $value == '{' ) {
            $this->stack[] = array( &$this->current, $this->state );
            unset( $this->current );
            $this->current = array(); // {}
            $this->state = self::STATE_IN_OBJECT;
            return $this->current;
        }
        elseif( $value == '[' ) {
            $this->stack[] = array( &$this->current, $this->state );
            unset( $this->current );
            $this->current = array(); // []
            $this->state = self::STATE_IN_ARRAY;
            return $this->current;
        }
        elseif( preg_match( self::REGEX_MULITILINE_START, $value ) > 0 ) {    // Multiline string
            $this->stack[] = array( &$this->current, $this->state );
            unset( $this->current );
            $this->current = "";
            $this->state = self::STATE_IN_MULTISTRING;
            $this->multistring_string = '';
            $this->multistring_end = $value;
            return $this->multistring_string;
        }
        else {
            if( $this->state == self::STATE_IN_ARRAY ) {
                $str = $this->parse_inline_string_value( $value );  // We want to return a reference which has to reference a variable
                return $str;
            }
            elseif( $this->state == self::STATE_IN_OBJECT ) {
                $this->record_error( "Value without preceding : in object" );
                $null = null;  // We want to return a reference which has to reference a variable
                return $null;
            }
            else {
                $this->record_error( "Bad value format" );    // Should be impossible
                $null = null;  // We want to return a reference which has to reference a variable
                return $null;
            }
        }
    }

    private function parse_inline_string_value( $value )
    {
        if( $value == '\0' )
            return null;
        elseif( $value[0] == '"' && $value[strlen($value)-1] == '"' )   // Quoted string
            return $this->string_unescape( substr( $value, 1, -1 ) );
        else
            return $this->string_unescape( $value );    // Naked string - It's up to app to decide if it's an integer, bool etc.
    }

    private function string_unescape( $s )
    {
        // TODO - Implement full unescape handling
        return $s;
    }

    private function pop_stack()
    {
        if( count( $this->stack ) != 0 ) {
            // Be very careful to transfer $this->current as a reference
            $end = & $this->stack[count($this->stack) - 1];
            unset( $this->current );
            $this->current = & $end[0];
            $this->state = $end[1];
            array_pop( $this->stack );
        }
        elseif( ! $this->is_naked )
            $this->state = self::STATE_COMPLETE;
        else
            $this->record_error( "Unexpected '}' or '}' encountered" );
    }

    private function record_error( $msg )
    {
        $this->error_message = "Line {$this->line_num}: $msg";
        $this->state = self::STATE_ERRORED;
    }

    private function ends_with( $haystack, $needle )
    {
        $length = strlen( $needle );
        return substr( $haystack, -$length, $length );
    }

    public static function from_string( $str )
    {
        $parser = new LOON();
        $array = preg_split( '/\R/', $str );
        foreach( $array as $line ) {
            $parser->parse_line( $line );
        }
        if( ! $parser->is_good() )
            return new LOONError( $parser->error() );
        return $parser->result();
    }

    public static function from_file( $fname )
    {
        $parser = new LOON();
        $fin = @fopen( $fname, 'rt' );
        if( $fin ) {
            while( ($line = fgets( $fin )) !== false ) {
                $parser->parse_line( $line );
            }
            fclose( $fin );
        }
        if( ! $parser->is_good() )
            return new LOONError( $parser->error() );
        return $parser->result();
    }
}
?>
