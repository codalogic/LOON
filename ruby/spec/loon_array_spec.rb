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

require 'rspec'
require_relative '../lib/loon'

$bs = "\\"

describe 'loon' do
    it 'should return an Array when given an empty array' do
        v = LOON.parse "[\n]"
        expect( v.class ).to eq Array
    end

    it 'should return an Array when given an array with only a comment' do
        v = LOON.parse <<-End
            [
                # A comment
            ]
        End
        expect( v.class ).to eq Array
    end

    it 'should return a nil value when given an array with a member an explicit null value' do
        # Note: Double backslash is for Ruby escaping
        v = LOON.parse <<-End
            [
                #{$bs}0
            ]
        End
        expect( v.class ).to eq Array
        expect( v.length ).to eq 1
        expect( v[0] ).to be_nil
    end

    it 'should return an Array when given an array with an integer value' do
        v = LOON.parse <<-End
            [
                100
            ]
        End
        expect( v.class ).to eq Array
        expect( v[0] ).to eq "100"
    end

    it 'should return an Array with 2 elements when given an array with two integer values' do
        v = LOON.parse <<-End
            [
                100
                200
            ]
        End
        expect( v.class ).to eq Array
        expect( v[0] ).to eq "100"
        expect( v[1] ).to eq "200"
    end

    it 'should return an Array with 3 elements when given an array with integer - array - integer values' do
        v = LOON.parse <<-End
            [
                100
                [
                ]
                200
            ]
        End
        expect( v.class ).to eq Array
        expect( v[0] ).to eq "100"
        expect( v[1].class ).to eq Array
        expect( v[2] ).to eq "200"
    end

    it 'should return an Array with 3 elements when given an array with integer - array with member - integer values' do
        v = LOON.parse <<-End
            [
                100
                [
                    true
                ]
                200
            ]
        End
        expect( v.class ).to eq Array
        expect( v[0] ).to eq "100"
        expect( v[1].class ).to eq Array
        expect( v[1][0] ).to eq "true"
        expect( v[2] ).to eq "200"
    end

    it 'should return an Array with 3 elements when given an array with integer - object - integer values' do
        v = LOON.parse <<-End
            [
                100
                {
                }
                200
            ]
        End
        expect( v.class ).to eq Array
        expect( v[0] ).to eq "100"
        expect( v[1].class ).to eq Hash
        expect( v[2] ).to eq "200"
    end
end
