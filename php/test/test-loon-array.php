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

checkfeature( 'it should return an Array when given an empty array', function() {
    $v = LOON::from_string( "[\n]" );
    check( is_array( $v ), true );
    check( count( $v ), 0 );
});

checkfeature( 'it should return an Array when given an array with only a comment', function() {
    $loon = <<<End
        [
            # A comment
        ]
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 0 );
});

checkfeature( 'it should return a nil value when given an array with a member an explicit null value', function() {
    global $bs;
    $loon = <<<End
        [
            {$bs}0
        ]
    End;
    print_r( $loon );
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( $v[0], null );
});

checkfeature( 'it should return an Array when given an array with an integer value', function() {
    $loon = <<<End
        [
            100
        ]
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 1 );
    check( $v[0], "100" );
});

checkfeature( 'it should return an Array with 2 elements when given an array with two integer values', function() {
    $loon = <<<End
        [
            100
            200
        ]
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 2 );
    check( $v[0], "100" );
    check( $v[1], "200" );
});

checkfeature( 'it should ignore a comment in an array', function() {
    $loon = <<<End
        [
            100
            # A comment
            200
        ]
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 2 );
    check( $v[0], "100" );
    check( $v[1], "200" );
});

checkfeature( 'it should accept something that looks like a comment in a quoted string in an array', function() {
    $loon = <<<End
        [
            100
            "# A comment"
            200
        ]
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 3 );
    check( $v[0], "100" );
    check( $v[1], "# A comment" );
    check( $v[2], "200" );
});

checkfeature( 'it should return an Array with 3 elements when given an array with integer - array - integer values', function() {
    $loon = <<<End
        [
            100
            [
            ]
            200
        ]
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 3 );
    check( $v[0], "100" );
    check( is_array( $v[1] ), true );
    check( $v[2], "200" );
});

checkfeature( 'it should return an Array with 3 elements when given an array with integer - array with member - integer values', function() {
    $loon = <<<End
        [
            100
            [
                true
            ]
            200
        ]
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 3 );
    check( $v[0], "100" );
    check( is_array( $v[1] ), true );
    check( $v[1][0], "true" );
    check( $v[2], "200" );
});

checkfeature( 'it should return an Array with 3 elements when given an array with integer - object - integer values', function() {
    $loon = <<<End
        [
            100
            {
            }
            200
        ]
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( count( $v ), 3 );
    check( $v[0], "100" );
    check( 'is_array( $v[1] )', is_array( $v[1] ), true );
    check( $v[2], "200" );
});

?>
