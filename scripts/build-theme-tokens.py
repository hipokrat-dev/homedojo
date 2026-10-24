"""Make legacy component colors mode-aware while preserving their dark defaults.

Only stylesheet colors are tokenized; artwork, photos and canvas colors are untouched.
Run again after adding legacy colors. Modern UI below the marker uses semantic tokens.
"""
from pathlib import Path
import colorsys
import re

path = Path(__file__).resolve().parents[1] / 'public/style.css'
css = path.read_text()
marker = '/* APPEARANCE SYSTEM */'
legacy, _, modern = css.partition(marker)
legacy = re.sub(r'/\* GENERATED LIGHT TOKENS \*/.*?/\* END LIGHT TOKENS \*/\n?', '', legacy, flags=re.S)
legacy = re.sub(r'var\(--tone-([0-9a-f]+),#[0-9a-f]+\)', lambda m: '#' + m[1], legacy)
tokens = {}

def token(match):
    short = match[1].lower()
    expanded = ''.join(c * 2 for c in short) if len(short) in (3, 4) else short
    rgb = expanded[:6]
    # Keep black shadows and media surfaces physically dark.
    if rgb == '000000':
        return match[0]
    h, l, s = colorsys.rgb_to_hls(*(int(rgb[i:i+2], 16) / 255 for i in (0, 2, 4)))
    stops = [(0, .99), (.15, .96), (.30, .84), (.45, .50), (.60, .36), (.80, .25), (1, .12)]
    for (x0, y0), (x1, y1) in zip(stops, stops[1:]):
        if l <= x1:
            target = y0 + (y1-y0) * (l-x0)/(x1-x0)
            break
    converted = colorsys.hls_to_rgb(h, target, min(s, .65))
    tokens[short] = '#' + ''.join(f'{round(c*255):02x}' for c in converted) + expanded[6:]
    return f'var(--tone-{short},#{short})'

legacy = re.sub(r'#([0-9a-fA-F]{8}|[0-9a-fA-F]{6}|[0-9a-fA-F]{4}|[0-9a-fA-F]{3})(?![0-9a-fA-F\w-])', token, legacy)
light = '/* GENERATED LIGHT TOKENS */\n:root[data-mode="light"]{\n' + ''.join(f' --tone-{key}:{value};\n' for key, value in sorted(tokens.items())) + '}\n/* END LIGHT TOKENS */\n'
path.write_text(legacy.rstrip() + '\n\n' + light + (marker + modern if modern else ''))
print(f'{len(tokens)} legacy colors support light mode')
