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
    it 'should return a Hash when given an empty object' do
        v = LOON.parse "{\n}"
        expect( v.class ).to eq Hash
    end

    it 'should return a Hash when given an object with only a comment' do
        v = LOON.parse <<-End
            {
                # A comment
            }
        End
        expect( v.class ).to eq Hash
    end

    it 'should return a nil value when given an object with a member without a value part' do
        v = LOON.parse <<-End
            {
                myNil    
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 'myNil' ).to be true
        expect( v['myNil'] ).to be_nil
    end

    it 'should return a nil value when given an object with a member an explicit null value part' do
        # Note: $bs -> Backslash
        v = LOON.parse <<-End
            {
                myNil : #{$bs}0
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 'myNil' ).to be true
        expect( v['myNil'] ).to be_nil
    end
end
