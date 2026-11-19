param([switch]$StaticOnly)

$ErrorActionPreference = 'Stop'

$root = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$exports = Join-Path $root 'social-media\exports'
$work = Join-Path $root 'social-media\work'
$source = Join-Path $root 'social-media\source'

New-Item -ItemType Directory -Force -Path $exports, $work | Out-Null

$bundledFfmpeg = 'C:\Users\van\.codex\visualizations\2026\07\22\019f8734-0291-74b2-9747-783edcab8443\van-newborn-final\ffmpeg-package\extracted\imageio_ffmpeg\binaries\ffmpeg-win-x86_64-v7.1.exe'
$ffmpegCommand = Get-Command ffmpeg -ErrorAction SilentlyContinue
if ($ffmpegCommand) {
    $ffmpeg = $ffmpegCommand.Source
} elseif (Test-Path -LiteralPath $bundledFfmpeg) {
    $ffmpeg = $bundledFfmpeg
} else {
    throw 'FFmpeg was not found. Install FFmpeg or update $bundledFfmpeg.'
}

function Invoke-Ffmpeg {
    param([Parameter(Mandatory)][string[]]$Arguments)
    & $ffmpeg @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "FFmpeg failed with exit code $LASTEXITCODE."
    }
}

function Require-File {
    param([Parameter(Mandatory)][string]$Path)
    if (-not (Test-Path -LiteralPath $Path)) {
        throw "Required media is missing: $Path"
    }
}

$narration = Join-Path $root 'assets\audio\mill-creek-promo-narration-van-v1.mp3'
$clipA = Join-Path $root 'assets\videos\classroom-showcase-1.mp4'
$clipB = Join-Path $root 'assets\videos\classroom-showcase-2.mp4'
$crest = Join-Path $root 'assets\images\mill-creek-icon-transparent.png'
$photoWide = Join-Path $root 'assets\images\classroom-coding-wide.jpeg'
$photoHands = Join-Path $root 'assets\images\classroom-coding-close.jpeg'
$photoGroup = Join-Path $root 'assets\images\classroom-workstations-group.jpeg'

@($narration, $clipA, $clipB, $crest, $photoWide, $photoHands, $photoGroup) | ForEach-Object { Require-File $_ }

$audioFull = Join-Path $work 'narration-full.wav'
$audio30 = Join-Path $work 'narration-30s.wav'
$audio16 = Join-Path $work 'narration-16s.wav'

if (-not $StaticOnly) {
Invoke-Ffmpeg @(
    '-nostdin','-v','error','-y','-i',$narration,
    '-af','atrim=start=0.07:end=51.186,asetpts=PTS-STARTPTS,loudnorm=I=-16:LRA=7:TP=-1.5',
    '-ar','48000','-ac','1','-c:a','pcm_s16le',$audioFull
)

Invoke-Ffmpeg @(
    '-nostdin','-v','error','-y','-i',$narration,
    '-filter_complex','[0:a]atrim=start=0.07:end=21.616,asetpts=PTS-STARTPTS[a0];[0:a]atrim=start=44.53:end=51.186,asetpts=PTS-STARTPTS[a1];[a0][a1]concat=n=2:v=0:a=1,loudnorm=I=-16:LRA=7:TP=-1.5[a]',
    '-map','[a]','-ar','48000','-ac','1','-c:a','pcm_s16le',$audio30
)

Invoke-Ffmpeg @(
    '-nostdin','-v','error','-y','-i',$narration,
    '-filter_complex','[0:a]atrim=start=0.07:end=9.035,asetpts=PTS-STARTPTS[a0];[0:a]atrim=start=44.53:end=51.186,asetpts=PTS-STARTPTS[a1];[a0][a1]concat=n=2:v=0:a=1,loudnorm=I=-16:LRA=7:TP=-1.5[a]',
    '-map','[a]','-ar','48000','-ac','1','-c:a','pcm_s16le',$audio16
)
}

function Render-Video {
    param(
        [Parameter(Mandatory)][string]$Output,
        [Parameter(Mandatory)][int]$Width,
        [Parameter(Mandatory)][int]$Height,
        [Parameter(Mandatory)][ValidateSet('full','30','16')][string]$Version,
        [Parameter(Mandatory)][ValidateSet('cover','contain')][string]$Fit,
        [switch]$LandscapeFrame
    )

    if ($Version -eq 'full') {
        $segments = @(
            @{ file=$clipA; start=0; duration=6.5 },
            @{ file=$clipB; start=0; duration=6.5 },
            @{ file=$clipA; start=12; duration=6.5 },
            @{ file=$clipB; start=18; duration=6.5 },
            @{ file=$clipA; start=30; duration=6.5 },
            @{ file=$clipB; start=36; duration=6.5 },
            @{ file=$clipA; start=48; duration=6.5 },
            @{ file=$clipB; start=54; duration=5.616 }
        )
        $audio = $audioFull
        $subtitle = 'social-media/source/master-52s-burn.srt'
        $ctaStart = 44.46
        $captionMargin = 105
    } elseif ($Version -eq '30') {
        $segments = @(
            @{ file=$clipA; start=0; duration=7.0 },
            @{ file=$clipB; start=8; duration=7.0 },
            @{ file=$clipA; start=22; duration=7.0 },
            @{ file=$clipB; start=30; duration=7.202 }
        )
        $audio = $audio30
        $subtitle = 'social-media/source/cutdown-30s-burn.srt'
        $ctaStart = 21.546
        $captionMargin = if ($Height -eq 1920) { 105 } elseif ($Height -eq 1080 -and $Width -eq 1080) { 45 } else { 36 }
    } else {
        $segments = @(
            @{ file=$clipA; start=0; duration=4.0 },
            @{ file=$clipB; start=4; duration=4.0 },
            @{ file=$clipA; start=18; duration=4.0 },
            @{ file=$clipB; start=24; duration=3.621 }
        )
        $audio = $audio16
        $subtitle = 'social-media/source/cutdown-16s-burn.srt'
        $ctaStart = 8.965
        $captionMargin = 105
    }

    $arguments = [System.Collections.Generic.List[string]]::new()
    foreach ($segment in $segments) {
        $arguments.Add('-i')
        $arguments.Add($segment.file)
    }
    $logoIndex = $segments.Count
    $audioIndex = $logoIndex + 1
    $arguments.Add('-i'); $arguments.Add($crest)
    $arguments.Add('-i'); $arguments.Add($audio)

    $filters = [System.Collections.Generic.List[string]]::new()
    for ($i = 0; $i -lt $segments.Count; $i++) {
        $segment = $segments[$i]
        if ($Fit -eq 'cover') {
            $fitFilter = "scale=${Width}:${Height}:force_original_aspect_ratio=increase,crop=${Width}:${Height}"
        } else {
            $fitFilter = "scale=${Width}:${Height}:force_original_aspect_ratio=decrease,pad=${Width}:${Height}:(ow-iw)/2:(oh-ih)/2:color=0x133D34"
        }
        $filters.Add("[$i`:v]trim=start=$($segment.start):duration=$($segment.duration),setpts=PTS-STARTPTS,$fitFilter,fps=30,setsar=1[v$i]")
    }

    $concatInputs = -join (0..($segments.Count - 1) | ForEach-Object { "[v$_]" })
    $filters.Add("${concatInputs}concat=n=$($segments.Count):v=1:a=0[base]")

    $font = 'C\:/Windows/Fonts/seguisb.ttf'
    $visual = "[base]drawtext=fontfile='$font':text='TALENT IS EVERYWHERE.':fontcolor=white:fontsize=$([Math]::Round($Width * 0.066)):x=(w-text_w)/2:y=$([Math]::Round($Height * 0.18)):borderw=3:bordercolor=0x17231F:enable='between(t,0,1.78)',drawtext=fontfile='$font':text='OPPORTUNITY IS NOT.':fontcolor=0xF0B84B:fontsize=$([Math]::Round($Width * 0.066)):x=(w-text_w)/2:y=$([Math]::Round($Height * 0.18)):borderw=3:bordercolor=0x17231F:enable='between(t,1.78,3.75)'"

    if ($LandscapeFrame) {
        $visual += ",drawtext=fontfile='$font':text='ACCESS':fontcolor=0xF0B84B:fontsize=56:x=70:y=250:enable='lt(t,$ctaStart)',drawtext=fontfile='$font':text='BELIEF':fontcolor=white:fontsize=56:x=85:y=350:enable='lt(t,$ctaStart)',drawtext=fontfile='$font':text='TRAINING':fontcolor=white:fontsize=56:x=w-text_w-80:y=250:enable='lt(t,$ctaStart)',drawtext=fontfile='$font':text='OPPORTUNITY':fontcolor=0xF0B84B:fontsize=56:x=w-text_w-60:y=350:enable='lt(t,$ctaStart)'"
    }

    $visual += ",drawbox=x=0:y=0:w=iw:h=ih:color=0x133D34@0.96:t=fill:enable='gte(t,$ctaStart)',drawtext=fontfile='$font':text='SUPPORT THE JOURNEY':fontcolor=0xF0B84B:fontsize=$([Math]::Round($Width * 0.065)):x=(w-text_w)/2:y=$([Math]::Round($Height * 0.28)):enable='gte(t,$ctaStart)',drawtext=fontfile='$font':text='millcreek-ar-learning.com':fontcolor=white:fontsize=$([Math]::Round($Width * 0.034)):x=(w-text_w)/2:y=$([Math]::Round($Height * 0.39)):enable='gte(t,$ctaStart)'[branded]"
    $filters.Add($visual)

    $captionFontSize = if ($Height -eq 1920) { 18 } elseif ($Width -eq 1080 -and $Height -eq 1080) { 18 } else { 24 }
    $filters.Add("[branded]subtitles='$subtitle':force_style='FontName=Segoe UI Semibold,FontSize=$captionFontSize,PrimaryColour=&H00FFFFFF,OutlineColour=&H0017231F,BorderStyle=1,Outline=2,Shadow=1,Alignment=2,MarginL=44,MarginR=70,MarginV=$captionMargin'[captioned]")
    $logoSize = if ($Height -eq 1920) { 190 } else { 150 }
    $logoY = if ($Height -eq 1920) { 250 } else { 80 }
    $filters.Add("[$logoIndex`:v]scale=${logoSize}:${logoSize}[logo];[captioned][logo]overlay=x=(W-w)/2:y=${logoY}:enable='gte(t,$ctaStart)'[vout]")

    $filterGraph = $filters -join ';'
    $arguments.InsertRange(0, [string[]]@('-nostdin','-v','error','-y'))
    $arguments.Add('-filter_complex'); $arguments.Add($filterGraph)
    $arguments.Add('-map'); $arguments.Add('[vout]')
    $arguments.Add('-map'); $arguments.Add("$audioIndex`:a:0")
    $arguments.Add('-c:v'); $arguments.Add('libx264')
    $arguments.Add('-preset'); $arguments.Add('medium')
    $arguments.Add('-crf'); $arguments.Add('20')
    $arguments.Add('-pix_fmt'); $arguments.Add('yuv420p')
    $arguments.Add('-r'); $arguments.Add('30')
    $arguments.Add('-c:a'); $arguments.Add('aac')
    $arguments.Add('-b:a'); $arguments.Add('192k')
    $arguments.Add('-ar'); $arguments.Add('48000')
    $arguments.Add('-movflags'); $arguments.Add('+faststart')
    $arguments.Add($Output)
    Invoke-Ffmpeg $arguments.ToArray()
}

if (-not $StaticOnly) {
    Render-Video -Output (Join-Path $exports 'video-vertical-52s.mp4') -Width 1080 -Height 1920 -Version full -Fit cover
    Render-Video -Output (Join-Path $exports 'video-vertical-30s.mp4') -Width 1080 -Height 1920 -Version 30 -Fit cover
    Render-Video -Output (Join-Path $exports 'video-vertical-16s.mp4') -Width 1080 -Height 1920 -Version 16 -Fit cover
    Render-Video -Output (Join-Path $exports 'video-square-30s.mp4') -Width 1080 -Height 1080 -Version 30 -Fit contain
    Render-Video -Output (Join-Path $exports 'video-landscape-30s.mp4') -Width 1920 -Height 1080 -Version 30 -Fit contain -LandscapeFrame
}

function Render-Static {
    param(
        [Parameter(Mandatory)][string]$Output,
        [Parameter(Mandatory)][string]$Photo,
        [Parameter(Mandatory)][int]$Width,
        [Parameter(Mandatory)][int]$Height,
        [Parameter(Mandatory)][string]$LineOne,
        [Parameter(Mandatory)][string]$LineTwo,
        [Parameter(Mandatory)][string]$Subline
    )

    $font = 'C\:/Windows/Fonts/seguisb.ttf'
    $titleSize = [Math]::Round($Width * 0.065)
    $subSize = [Math]::Round($Width * 0.033)
    $urlSize = [Math]::Round($Width * 0.027)
    $logoSize = [Math]::Round([Math]::Min($Width, $Height) * 0.14)
    $titleY = [Math]::Round($Height * 0.52)
    $left = [Math]::Round($Width * 0.08)
    $lineTwoY = $titleY + [Math]::Round($titleSize * 1.18)
    $subY = $titleY + [Math]::Round($titleSize * 2.62)
    $urlBottom = [Math]::Round($Height * 0.08)
    $logoY = [Math]::Round($Height * 0.08)
    $filter = "[0:v]scale=${Width}:${Height}:force_original_aspect_ratio=increase,crop=${Width}:${Height},setsar=1,drawbox=x=0:y=0:w=iw:h=ih:color=0x133D34@0.58:t=fill,drawbox=x=0:y=0:w=$([Math]::Round($Width * 0.02)):h=ih:color=0xF0B84B:t=fill,drawtext=fontfile='$font':text='$LineOne':fontcolor=white:fontsize=${titleSize}:x=${left}:y=${titleY}:borderw=2:bordercolor=0x17231F,drawtext=fontfile='$font':text='$LineTwo':fontcolor=0xF0B84B:fontsize=${titleSize}:x=${left}:y=${lineTwoY}:borderw=2:bordercolor=0x17231F,drawtext=fontfile='$font':text='$Subline':fontcolor=white:fontsize=${subSize}:x=${left}:y=${subY}:borderw=1:bordercolor=0x17231F,drawtext=fontfile='$font':text='millcreek-ar-learning.com':fontcolor=white:fontsize=${urlSize}:x=${left}:y=h-${urlBottom}[base];[1:v]scale=${logoSize}:${logoSize}[logo];[base][logo]overlay=x=${left}:y=${logoY}[out]"
    Invoke-Ffmpeg @(
        '-nostdin','-v','error','-y','-i',$Photo,'-i',$crest,
        '-filter_complex',$filter,'-map','[out]','-frames:v','1',$Output
    )
}

Render-Static -Output (Join-Path $exports 'image-square-1080.png') -Photo $photoHands -Width 1080 -Height 1080 -LineOne 'TALENT IS EVERYWHERE.' -LineTwo 'OPPORTUNITY IS NOT.' -Subline 'Technology learning in Accra, Ghana'
Render-Static -Output (Join-Path $exports 'image-portrait-1080x1350.png') -Photo $photoGroup -Width 1080 -Height 1350 -LineOne 'ACCESS. BELIEF.' -LineTwo 'TRAINING. OPPORTUNITY.' -Subline 'Support a sustained learning journey'
Render-Static -Output (Join-Path $exports 'image-story-1080x1920.png') -Photo $photoHands -Width 1080 -Height 1920 -LineOne 'SEE LEARNING' -LineTwo 'IN MOTION.' -Subline 'Coding and digital skills in Accra'
Render-Static -Output (Join-Path $exports 'image-landscape-1200x628.png') -Photo $photoWide -Width 1200 -Height 628 -LineOne 'FROM MILL CREEK' -LineTwo 'TO ACCRA.' -Subline 'A bridge built through technology education'
Render-Static -Output (Join-Path $exports 'image-youtube-thumbnail-1280x720.png') -Photo $photoWide -Width 1280 -Height 720 -LineOne 'OPPORTUNITY' -LineTwo 'THROUGH TECHNOLOGY' -Subline 'Accra, Ghana'

Copy-Item -LiteralPath (Join-Path $source 'master-52s.srt') -Destination (Join-Path $exports 'video-vertical-52s.srt') -Force
Copy-Item -LiteralPath (Join-Path $source 'cutdown-30s.srt') -Destination (Join-Path $exports 'video-30s.srt') -Force
Copy-Item -LiteralPath (Join-Path $source 'cutdown-16s.srt') -Destination (Join-Path $exports 'video-16s.srt') -Force

Write-Output "Rendered social package to $exports"
