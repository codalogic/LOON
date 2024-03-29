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
require 'pp'
require_relative '../lib/loon'

describe 'loon' do
    it 'should return a Hash when given an empty string' do
        v = LOON.parse ""
        expect( v.class ).to eq Hash
    end

    it 'should return a Hash when given only a comment' do
        v = LOON.parse "# A comment"
        expect( v.class ).to eq Hash
    end

    it 'should return a Hash when given a naked object' do
        v = LOON.parse <<-End
            s : A String
            n: 100
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'] ).to eq "A String"
        expect( v.include? 'n' ).to be true
        expect( v['n'] ).to eq "100"
    end

    it 'should return a Hash when given a naked object containg a sub-object' do
        v = LOON.parse <<-End
            s : A String
            n: 100
            o {
                p
            }
        End
        expect( v.class ).to eq Hash
        expect( v.include? 's' ).to be true
        expect( v['s'] ).to eq "A String"
        expect( v.include? 'n' ).to be true
        expect( v['n'] ).to eq "100"
        expect( v.include? 'o' ).to be true
        expect( v['o'].class ).to eq Hash
        expect( v['o'].include? 'p' ).to be true
        expect( v['o']['p'] ).to be nil
    end
end
