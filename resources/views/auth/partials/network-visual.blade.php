{{--
    Login side panel: animated 3D "digital connections" scene (decorative only).
    Built from SVG + CSS (no image file), so it stays sharp at any size and can move:
    floating spheres & glass cards, glowing node network with flowing lines, particles (canvas) and pointer parallax.
    Composition keeps the busy part on the right of a 4:5 stage; the clean left/bottom area carries the panel text.
--}}
@php
    // Network nodes in a 400 x 500 (4:5) space; mostly on the right half.
    $nodes = [
        [300, 78, 7], [366, 150, 5], [246, 168, 6], [330, 246, 9], [214, 290, 5],
        [376, 330, 6], [278, 360, 7], [196, 420, 4], [346, 430, 5], [158, 206, 4], [384, 248, 3], [250, 470, 3],
    ];
    $edges = [[0, 1], [0, 2], [1, 3], [2, 3], [2, 9], [3, 4], [3, 5], [3, 6], [4, 6], [5, 8], [6, 8], [6, 7], [4, 9], [1, 10], [5, 10], [7, 11], [8, 11], [0, 3]];
    $curve = function ($a, $b, $bend = 0.18) {
        [$x1, $y1] = $a; [$x2, $y2] = $b;
        $mx = ($x1 + $x2) / 2; $my = ($y1 + $y2) / 2;
        $cx = $mx - ($y2 - $y1) * $bend; $cy = $my + ($x2 - $x1) * $bend;
        return sprintf('M%.1f %.1f Q%.1f %.1f %.1f %.1f', $x1, $y1, $cx, $cy, $x2, $y2);
    };
@endphp
<div class="nv" data-nv>
    <div class="nv-mesh"><i></i><i></i><i></i><i></i></div>
    <canvas class="nv-particles" data-nv-particles></canvas>

    <div class="nv-art">
        {{-- Back layer: flowing curves --}}
        <svg class="nv-layer nv-flow" data-depth="0.4" viewBox="0 0 400 500" preserveAspectRatio="xMidYMid meet">
            <defs>
                <linearGradient id="nvFlowA" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0" stop-color="#4F7BFF" stop-opacity="0"/>
                    <stop offset=".45" stop-color="#6D8BFF"/>
                    <stop offset="1" stop-color="#C084FC"/>
                </linearGradient>
                <linearGradient id="nvFlowB" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0" stop-color="#A78BFA" stop-opacity="0"/>
                    <stop offset=".6" stop-color="#A78BFA"/>
                    <stop offset="1" stop-color="#F0ABFC"/>
                </linearGradient>
            </defs>
            <path class="nv-wave nv-wave-1" d="M-20 420 C 90 360, 170 470, 260 390 S 380 300, 430 330" stroke="url(#nvFlowA)"/>
            <path class="nv-wave nv-wave-2" d="M-20 455 C 110 410, 190 500, 290 430 S 390 360, 430 380" stroke="url(#nvFlowB)"/>
            <path class="nv-wave nv-wave-3" d="M60 60 C 160 120, 220 40, 300 110 S 400 170, 430 120" stroke="url(#nvFlowA)"/>
        </svg>

        {{-- Torus ring --}}
        <div class="nv-layer nv-ring-wrap" data-depth="0.8"><div class="nv-ring"></div></div>

        {{-- Network --}}
        <svg class="nv-layer nv-net" data-depth="1" viewBox="0 0 400 500" preserveAspectRatio="xMidYMid meet">
            <defs>
                <linearGradient id="nvEdge" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#7DA2FF"/>
                    <stop offset="1" stop-color="#C084FC"/>
                </linearGradient>
                <radialGradient id="nvNode" cx=".35" cy=".35" r=".7">
                    <stop offset="0" stop-color="#FFFFFF"/>
                    <stop offset=".35" stop-color="#C9D6FF"/>
                    <stop offset="1" stop-color="#7955E8"/>
                </radialGradient>
                <filter id="nvGlow" x="-200%" y="-200%" width="500%" height="500%">
                    <feGaussianBlur stdDeviation="5"/>
                </filter>
            </defs>
            <g class="nv-edges">
                @foreach ($edges as $k => [$a, $b])
                    @php($d = $curve($nodes[$a], $nodes[$b], $k % 2 ? 0.16 : -0.16))
                    <path class="nv-edge" d="{{ $d }}"/>
                    <path class="nv-edge-flow" d="{{ $d }}" style="--i: {{ $k }}"/>
                @endforeach
            </g>
            <g class="nv-nodes">
                @foreach ($nodes as $k => [$x, $y, $r])
                    <g class="nv-node" style="--i: {{ $k }}">
                        <circle class="nv-node-glow" cx="{{ $x }}" cy="{{ $y }}" r="{{ $r * 2.2 }}" filter="url(#nvGlow)"/>
                        <circle class="nv-node-pulse" cx="{{ $x }}" cy="{{ $y }}" r="{{ $r }}"/>
                        <circle class="nv-node-core" cx="{{ $x }}" cy="{{ $y }}" r="{{ $r }}"/>
                    </g>
                @endforeach
            </g>
        </svg>

        {{-- Glass cards (abstract, no UI text) --}}
        <div class="nv-layer" data-depth="1.6">
            <div class="nv-glass nv-glass-1">
                <span class="nv-g-orb"></span>
                <span class="nv-g-line w-70"></span><span class="nv-g-line w-45"></span>
                <span class="nv-g-chain"><i></i><i></i><i></i></span>
            </div>
        </div>
        <div class="nv-layer" data-depth="2.2">
            <div class="nv-glass nv-glass-2">
                <span class="nv-g-bars"><i style="--h:40%"></i><i style="--h:65%"></i><i style="--h:50%"></i><i style="--h:85%"></i><i style="--h:70%"></i></span>
            </div>
        </div>
        <div class="nv-layer" data-depth="1.2">
            <div class="nv-glass nv-glass-3"><span class="nv-g-dot"></span><span class="nv-g-line w-60"></span></div>
        </div>

        {{-- 3D spheres & soft geometric shapes --}}
        <div class="nv-layer" data-depth="2.6"><span class="nv-sphere nv-sphere-1"></span></div>
        <div class="nv-layer" data-depth="1.8"><span class="nv-sphere nv-sphere-2"></span></div>
        <div class="nv-layer" data-depth="3.2"><span class="nv-sphere nv-sphere-3"></span></div>
        <div class="nv-layer" data-depth="0.9"><span class="nv-sphere nv-sphere-4"></span></div>
        <div class="nv-layer" data-depth="2"><span class="nv-cube"><i></i><i></i><i></i><i></i><i></i><i></i></span></div>
        <div class="nv-layer" data-depth="1.4"><span class="nv-pill"></span></div>
    </div>

    <div class="nv-vignette"></div>
</div>
