<?php
#----------------------------------------------------------------------------
# Licensed under the MIT/X11 license - https://opensource.org/licenses/MIT
#----
# Copyright (c) 2021, Codalogic Ltd (www.codalogic.com)
#
# Permission is hereby granted, free of charge, to any person obtaining a
# copy of this software and associated documentation files (the "Software"),
# to deal in the Software without restriction, including without limitation
# the rights to use, copy, modify, merge, publish, distribute, sublicense,
# and/or sell copies of the Software, and to permit persons to whom the
# Software is furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included
# in all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
# THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
# FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
# DEALINGS IN THE SOFTWARE.
#----------------------------------------------------------------------------

$bs = "\\";

checkfeature( 'it should return a string if given an object with a naked string', function() {
    $loon = <<<End
        {
            s : My string here
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( $v['s'], "My string here" );
});

checkfeature( 'it should return a string if given an object with a quoted string with leading a trailing whitespace', function() {
    $loon = <<<End
        {
            s : "  My string here  "
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( $v['s'], "  My string here  " );
});

checkfeature( 'it should return a string if given an object with a quoted string with internal quotation marks', function() {
    $loon = <<<End
        {
            s : "My string here with ""
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( $v['s'], "My string here with \"" );
});

checkfeature( 'it should return a string if given an object with a multi line string', function() {
    $loon = <<<End
        {
            s <<END
            My string
            Other string
            <<END
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( preg_replace( '/\n\s*/', "\n", ltrim( $v['s'] ) ), "My string\nOther string\n" );
});

checkfeature( 'it should return a string if given an object with a multi line string without final end-of-line', function() {
    $loon = <<<End
        {
            s <<END
            My string
            Other string <<END
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( preg_replace( '/\n\s*/', "\n", ltrim( $v['s'] ) ), "My string\nOther string " );
});

checkfeature( 'it should return a string if given an object with a multi line string with preamble characters', function() {
    $loon = <<<End
        {
            s <<END "...."
    ....    My string
    ....    Other string <<END
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( preg_replace( '/\n\s*/', "\n", ltrim( $v['s'] ) ), "My string\nOther string " );
});

checkfeature( 'it should return a string that includes comment text if given an object with a multi line string with comment text', function() {
    $loon = <<<End
        {
            s <<END
            My string
# Not a comment as it's in a multi-line string
            Other string <<END
        }
End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( preg_replace( '/\n\s*/', "\n", ltrim( $v['s'] ) ), "My string\n# Not a comment as it's in a multi-line string\nOther string " );
});

// TODO - Enable \ escaping in strings
checktodo( 'Enable \ escaping in strings' );
/*
checkfeature( 'it should return a string with a tab in if given an object with a string with a \t', function() {
    # Note: $bs -> Backslash
    global $bs;
    $loon = <<<End
        {
            s : String with '#{$bs}t' in the middle
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( $v['s'], "String with '\u0009' in the middle" );     // \u0009 == \t == TAB
});

checkfeature( 'it should return a string with a tab in if given an object with a string with a \u0009', function() {
    # Note: $bs -> Backslash
    global $bs;
    $loon = <<<End
        {
            s : String with '#{$bs}u0009' in the middle
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( $v['s'], "String with '\t' in the middle" );     // \t == \u0009 == TAB
});

checkfeature( 'it should return a string with back-to-back tabs in if given an object with a string with a \t\u0009', function() {
    # Note: $bs -> Backslash
    global $bs;
    $loon = <<<End
        {
            s : String with '#{$bs}t#{$bs}u0009' in the middle
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( $v['s'], "String with '\u0009\t' in the middle" );     // \t == \u0009 == TAB
});

checkfeature( 'it should return a string with a euro symbol in if given an object with a string with a \u0009', function() {
    # Note: $bs -> Backslash
    global $bs;
    $loon = <<<End
        {
            s : String with #{$bs}u20ac in the middle
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( $v['s'], "String with \u20ac in the middle" );
});

checkfeature( 'it should return a string with non-surrogate if given an object with a string with a surrogate pair', function() {
    # Note: $bs -> Backslash
    global $bs;
    $loon = <<<End
        {
            s : String with #{$bs}uD800#{$bs}uDEAD in the middle
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( $v['s'], "String with \u{102AD} in the middle" );
});

checkfeature( 'it should return a string with non-BMP character if given an object with a string with a \u{XXXXXX} code', function() {
    # Note: $bs -> Backslash
    global $bs;
    $loon = <<<End
        {
            s : String with #{$bs}u{0102AD} in the middle
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( $v['s'], "String with \u{102AD} in the middle" );
});

checkfeature( 'it should return a string with a tab if given an object with a string with a \u{XXXXXX} code', function() {
    # Note: $bs -> Backslash
    global $bs;
    $loon = <<<End
        {
            s : String with '#{$bs}u{9}' in the middle
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( $v['s'], "String with '\t' in the middle" );     // \t == \u0009 == TAB
});

checkfeature( 'should return a string with a \u{102AD} in the middle', function() {
    # Note: $bs -> Backslash
    global $bs;
    $loon = <<<End
        {
            s : String with #{$bs}u{0102AD} in the middle
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( $v['s'], "String with \u{102AD} in the middle" );
});

checkfeature( 'it should return a string with a tab in if given an object with a multi line string with a \t', function() {
    # Note: $bs -> Backslash
    global $bs;
    $loon = <<<End
        {
            s <<END
            String with '#{$bs}t' in the middle
            <<END
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( trim( $v['s'] ), "String with '\u0009' in the middle" );     // \u0009 == \t == TAB
});

checkfeature( 'it should return a string with a tab in if given an object with a multi line string with a \u0009', function() {
    # Note: $bs -> Backslash
    global $bs;
    $loon = <<<End
        {
            s <<END
            String with '#{$bs}u0009' in the middle
            <<END
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( trim( $v['s'] ), "String with '\t' in the middle" );     // \t == \u0009 == TAB
});

checkfeature( 'it should return a string with a euro symbol in if given an object with a multi line string with a \u0009', function() {
    # Note: $bs -> Backslash
    global $bs;
    $loon = <<<End
        {
            s <<END
            String with #{$bs}u20ac in the middle
            <<END
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( trim( $v['s'] ), "String with \u20ac in the middle" );
});

checkfeature( 'it should return a string with non-surrogate if given an object with a multi line string with a surrogate pair', function() {
    # Note: $bs -> Backslash
    global $bs;
    $loon = <<<End
        {
            s <<END
            String with #{$bs}uD800#{$bs}uDEAD in the middle
            <<END
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( trim( $v['s'] ), "String with \u{102AD} in the middle" );
});

checkfeature( 'it should return a string with non-BMP character if given an object with a multi line string with a \u{XXXXXX} code', function() {
    # Note: $bs -> Backslash
    global $bs;
    $loon = <<<End
        {
            s <<END
            String with #{$bs}u{0102AD} in the middle
            <<END
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( trim( $v['s'] ), "String with \u{102AD} in the middle" );
});

checkfeature( 'it should return a string with tab character if given an object with a multi line string with a \u{9} code', function() {
    # Note: $bs -> Backslash
    global $bs;
    $loon = <<<End
        {
            s <<END
            String with #{$bs}u{9} in the middle
            <<END
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( isset( $v['s'] ), true );
    check( trim( $v['s'] ), "String with \t in the middle" );
});
/*/
//*/
?>
