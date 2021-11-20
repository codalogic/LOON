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

checkfeature( 'should return a Hash when given an empty string', function() {
    $v = LOON::from_string( "" );
    check( is_array( $v ), true );
});

checkfeature( 'should return a Hash when given only a comment', function() {
    $v = LOON::from_string( "# A comment" );
    check( is_array( $v ), true );
});

checkfeature( 'should return a Hash when given a naked object', function() {
    $loon = <<<End
        s : A String
        n: 100
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( isset( $v['s'] ), true );
    check( $v['s'], "A String" );
    check( isset( $v['s'] ), true );
    check( $v['n'], "100" );
});

checkfeature( 'should return a Hash when given a naked object containg a sub-object', function() {
    $loon = <<<End
        s : A String
        n: 100
        o {
            p
        }
    End;
    $v = LOON::from_string( $loon );
    check( is_array( $v ), true );
    check( isset( $v['s'] ), true );
    check( $v['s'], "A String" );
    check( isset( $v['s'] ), true );
    check( $v['n'], "100" );
    check( isset( $v['o'] ), true );
    check( is_array( $v['o'] ), true );
    check( array_key_exists( 'p', $v['o'] ), true );
    check( $v['o']['p'], null );
});
