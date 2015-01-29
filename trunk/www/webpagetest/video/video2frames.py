#-------------------------------------------------------------------------------
# Name:        video2frames
# Purpose:     Convert a video to individual unique frames
#
# Copyright (c) 2013, Google Inc.
# All rights reserved.
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions are met:
#
#   * Redistributions of source code must retain the above copyright notice,
#     this list of conditions and the following disclaimer.
#   * Redistributions in binary form must reproduce the above copyright notice,
#     this list of conditions and the following disclaimer in the documentation
#     and/or other materials provided with the distribution.
#   * Neither the name of the <ORGANIZATION> nor the names of its contributors
#     may be used to endorse or promote products derived from this software
#     without specific prior written permission.
#
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
# AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
# IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
# DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
# FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
# DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
# SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
# OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
# OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
#-------------------------------------------------------------------------------

import sys
import os
import subprocess
import re

def execCommand(cmd):
    out = '';
    process = subprocess.Popen(cmd, shell=True,
                           stdout=subprocess.PIPE,
                           stderr=subprocess.PIPE)

    # wait for the process to terminate
    for line in process.stdout:
        out += line
    errcode = process.returncode
    return out

def ExtractFramesFast(videoFile, outDir):
    if not os.path.exists(outDir):
        os.makedirs(outDir)
    cmd = "ffmpeg -v debug -i \"" + videoFile + "\" -vsync 0 -vf \"fps=fps=10,scale=iw*min(400/iw\,400/ih):ih*min(400/iw\,400/ih),decimate\" \"" + outDir + "/img-%d.png\" 2>&1"
    process = subprocess.Popen(cmd, shell=True,
                           stdout=subprocess.PIPE,
                           stderr=subprocess.PIPE)

    # process each non-duplicate frame and add the timestamp
    frameCount = 0
    out = '';
    for line in process.stdout:
        out += line
        matches = re.match(".*decimate.*pts:(?P<timecode>[0-9]+).*drop_count:-[0-9]+.*", line)
        if matches and 'timecode' in matches.groupdict():
            #print out
            out = ''
            frameCount += 1
            src = outDir + "/img-%d.png" % frameCount
            dst = outDir + "/image-%04d.png" % int(matches.groupdict()['timecode'])
            # os.rename(src, dst)
            print src + " -> " + dst
    #print line
    print "%d frames detected" % frameCount
    return frameCount

def ExtractFrames(videoFile, outDir):
    if not os.path.exists(outDir):
        os.makedirs(outDir)
    cmd = "ffmpeg -i \"" + videoFile + "\" -vf scale=\"'if(gt(a,4/3),320,-1)':'if(gt(a,4/3),-1,240)'\" -r 10 \"" + outDir + "/image-%04d.png\" 2>&1"
    process = subprocess.Popen(cmd, shell=True)
    result = process.wait()
    print result
    return result == 0

if len(sys.argv) != 3:
    print "Usage: video2frames <video file> <output directory>"
else:
    if ExtractFramesFast(sys.argv[1], sys.argv[2]):
        print "done"