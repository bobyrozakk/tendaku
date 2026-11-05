<svg class="absolute inset-0 h-full w-full" viewBox="0 0 600 800" preserveAspectRatio="xMidYMax slice" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="night" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#0A0A2C"/>
            <stop offset=".3" stop-color="#1B1860"/>
            <stop offset=".55" stop-color="#3D2F8F"/>
            <stop offset=".72" stop-color="#7A5BC0"/>
            <stop offset=".8" stop-color="#A98AD8"/>
        </linearGradient>
        <radialGradient id="horizon">
            <stop offset="0" stop-color="#B58AE0" stop-opacity=".55"/>
            <stop offset="1" stop-color="#B58AE0" stop-opacity="0"/>
        </radialGradient>
        <radialGradient id="fireglow">
            <stop offset="0" stop-color="#FBCE6B" stop-opacity=".7"/>
            <stop offset=".5" stop-color="#F29A1F" stop-opacity=".25"/>
            <stop offset="1" stop-color="#F29A1F" stop-opacity="0"/>
        </radialGradient>
        <radialGradient id="moonglow">
            <stop offset="0" stop-color="#FFF1CF" stop-opacity=".5"/>
            <stop offset=".12" stop-color="#F9E3B6" stop-opacity=".34"/>
            <stop offset=".28" stop-color="#D8C6F2" stop-opacity=".2"/>
            <stop offset=".5" stop-color="#A28CE6" stop-opacity=".09"/>
            <stop offset=".75" stop-color="#7A5BC0" stop-opacity=".03"/>
            <stop offset="1" stop-color="#7A5BC0" stop-opacity="0"/>
        </radialGradient>
        <radialGradient id="mooncore">
            <stop offset="0" stop-color="#FFF6DD" stop-opacity=".85"/>
            <stop offset=".5" stop-color="#F9E3B6" stop-opacity=".35"/>
            <stop offset="1" stop-color="#F9E3B6" stop-opacity="0"/>
        </radialGradient>
        <symbol id="pine" viewBox="0 0 40 100">
            <path d="M20 0 L33 30 H26 L37 56 H28 L40 84 H22 V100 H18 V84 H0 L12 56 H3 L14 30 H7Z"/>
        </symbol>
    </defs>

    <rect width="600" height="800" fill="url(#night)"/>
    <circle cx="300" cy="610" r="300" fill="url(#horizon)"/>

    {{-- bintang --}}
    <g fill="#F9E3B6" fill-opacity=".85">
        <circle cx="70" cy="60" r="2"/><circle cx="160" cy="120" r="1.6"/><circle cx="250" cy="50" r="2"/>
        <circle cx="340" cy="100" r="1.5"/><circle cx="560" cy="60" r="1.5"/><circle cx="110" cy="200" r="1.4"/>
        <circle cx="520" cy="230" r="1.4"/><circle cx="30" cy="140" r="1.3"/><circle cx="210" cy="250" r="1.3"/>
        <circle cx="390" cy="230" r="1.2"/><circle cx="300" cy="190" r="1.2"/><circle cx="570" cy="170" r="1.4"/>
    </g>


    {{-- hutan jauh --}}
    <path d="M0 600 C150 585 300 610 450 595 C520 588 570 596 600 600 V800 H0Z" fill="#6D5AB5"/>
    <g fill="#584A9E">
        <use href="#pine" x="10" y="490" width="30" height="110"/>
        <use href="#pine" x="55" y="460" width="38" height="140"/>
        <use href="#pine" x="105" y="500" width="28" height="100"/>
        <use href="#pine" x="150" y="470" width="36" height="130"/>
        <use href="#pine" x="215" y="505" width="26" height="95"/>
        <use href="#pine" x="400" y="480" width="32" height="120"/>
        <use href="#pine" x="455" y="450" width="40" height="150"/>
        <use href="#pine" x="505" y="495" width="30" height="105"/>
        <use href="#pine" x="550" y="465" width="36" height="135"/>
    </g>

    {{-- hutan tengah --}}
    <g fill="#3E5A1A">
        <use href="#pine" x="-15" y="450" width="55" height="205"/>
        <use href="#pine" x="40" y="490" width="44" height="165"/>
        <use href="#pine" x="95" y="440" width="56" height="215"/>
        <use href="#pine" x="380" y="510" width="38" height="145"/>
        <use href="#pine" x="440" y="460" width="52" height="195"/>
        <use href="#pine" x="495" y="420" width="60" height="235"/>
        <use href="#pine" x="555" y="480" width="46" height="175"/>
    </g>
    <path d="M0 660 C110 630 230 652 330 646 C430 640 520 626 600 650 V800 H0Z" fill="#2E4415"/>

    {{-- pohon pembingkai --}}
    <g fill="#18240E">
        <use href="#pine" x="-20" y="400" width="90" height="330"/>
        <use href="#pine" x="48" y="540" width="48" height="190"/>
        <use href="#pine" x="520" y="370" width="100" height="360"/>
        <use href="#pine" x="472" y="510" width="58" height="220"/>
    </g>
    <path d="M0 700 C120 676 250 700 340 694 C440 688 520 672 600 690 V800 H0Z" fill="#221B05"/>

    {{-- tenda --}}
    <g stroke="#D9C9F0" stroke-opacity=".4" stroke-width="1.4">
        <line x1="370" y1="588" x2="262" y2="724"/>
        <line x1="370" y1="588" x2="486" y2="722"/>
    </g>
    <polygon points="288,712 370,588 452,712" fill="#EBD39F"/>
    <polygon points="370,588 452,712 372,712" fill="#8F7660"/>
    <polyline points="288,712 370,588 452,712" fill="none" stroke="#D5A007" stroke-width="3" stroke-linejoin="round"/>
    <polygon points="346,712 372,646 398,712" fill="#2B2202"/>
    <polygon class="door-glow" points="346,712 372,646 398,712" fill="#FBCE6B"/>

    {{-- api unggun --}}
    <circle class="fire-glow" cx="216" cy="700" r="150" fill="url(#fireglow)"/>

    <g class="flame flame-l">
        <path d="M200 722 C188 716 190 702 198 690 C201 698 205 698 207 692 C214 702 212 716 200 722Z" fill="#E8790F"/>
        <path d="M201 720 C194 716 196 708 200 701 C203 706 206 708 207 712 C208 717 205 720 201 720Z" fill="#FBCE6B"/>
    </g>
    <g class="flame flame-r">
        <path d="M232 722 C244 716 242 702 234 690 C231 698 227 698 225 692 C218 702 220 716 232 722Z" fill="#E8790F"/>
        <path d="M231 720 C238 716 236 708 232 701 C229 706 226 708 225 712 C224 717 227 720 231 720Z" fill="#FBCE6B"/>
    </g>
    <g class="flame flame-c">
        <path d="M216 722 C194 714 196 690 210 664 C213 676 220 678 224 662 C242 686 240 712 216 722Z" fill="#E8790F"/>
        <path d="M216 720 C202 713 205 696 213 682 C216 690 222 690 225 682 C234 696 231 712 216 720Z" fill="#FBCE6B"/>
        <path d="M216 718 C209 713 211 704 216 696 C221 704 223 713 216 718Z" fill="#F9E3B6"/>
    </g>

    <g fill="#FBCE6B">
        <circle class="ember" cx="210" cy="680" r="2"   style="--dx:-16px;animation-delay:0s"/>
        <circle class="ember" cx="222" cy="676" r="1.6" style="--dx:14px;animation-delay:.7s"/>
        <circle class="ember" cx="216" cy="684" r="2.2" style="--dx:4px;animation-delay:1.4s"/>
        <circle class="ember" cx="206" cy="686" r="1.4" style="--dx:-8px;animation-delay:2.1s"/>
        <circle class="ember" cx="226" cy="684" r="1.5" style="--dx:20px;animation-delay:1s"/>
    </g>

    <rect x="186" y="716" width="60" height="10" rx="5" fill="#4B3606" transform="rotate(-10 216 721)"/>
    <rect x="186" y="716" width="60" height="10" rx="5" fill="#33260A" transform="rotate(10 216 721)"/>
    <g fill="#8E80B0">
        <ellipse cx="180" cy="728" rx="10" ry="6"/><ellipse cx="200" cy="734" rx="9" ry="5.5"/>
        <ellipse cx="232" cy="734" rx="9" ry="5.5"/><ellipse cx="252" cy="728" rx="10" ry="6"/>
    </g>


    <path d="M0 760 C120 738 260 770 400 752 C500 740 560 748 600 744 V800 H0Z" fill="#120D02"/>
</svg>
