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

checkfeature( 'it should return a Hash when given an object with only a comment', function() {
    $loon = <<<End
        {
            # A comment
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 0 );
});

checkfeature( 'it should return a nil value when given an object with a member without a value part', function() {
    $loon = <<<End
        {
            myNil    
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( array_key_exists( 'myNil', $v ), true );
    check( $v['myNil'], null );
});

checkfeature( 'it should return a nil value when given an object with a member an explicit null value part', function() {
    # Note: $bs -> Backslash
    global $bs;
    $loon = <<<End
        {
            myNil : {$bs}0
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( array_key_exists( 'myNil', $v ), true );
    check( $v['myNil'], null );
});

checkfeature( 'it should accept an object member name starting with an @', function() {
    $loon = <<<End
        {
            @name: Fred
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( array_key_exists( '@name', $v ), true );
    check( $v['@name'], "Fred" );
});

checkfeature( 'it should accept an object member name with a realm', function() {
    $loon = <<<End
        {
            org.example.name: Fred
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( array_key_exists( 'org.example.name', $v ), true );
    check( $v['org.example.name'], "Fred" );
});

checkfeature( 'it should accept an object member name with a realm and an @', function() {
    $loon = <<<End
        {
            org.example.@name: Fred
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( array_key_exists( 'org.example.@name', $v ), true );
    check( $v['org.example.@name'], "Fred" );
});

?>
