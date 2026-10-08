"""Build original HomeDojo launcher artwork; no external images or fonts."""
from pathlib import Path
from PIL import Image, ImageDraw, ImageFilter
import math
root=Path(__file__).resolve().parents[1]/'public/icons'
S=1024
im=Image.new('RGB',(S,S));px=im.load()
for y in range(S):
 for x in range(S):
  t=(x+y)/(2*S);glow=max(0,1-math.hypot(x-260,y-200)/850)
  px[x,y]=(int(71+37*glow+45*t),int(34+28*glow+4*t),int(167+53*glow-15*t))
d=ImageDraw.Draw(im)
d.ellipse((-220,-400,880,550),fill=(145,104,238),outline=(174,137,255),width=4)
d.ellipse((650,710,1200,1260),fill=(82,37,146))
shadow=Image.new('RGBA',(S,S));sd=ImageDraw.Draw(shadow)
sd.rounded_rectangle((270,410,770,830),radius=70,fill=(30,5,80,120));shadow=shadow.filter(ImageFilter.GaussianBlur(30));im=Image.alpha_composite(im.convert('RGBA'),shadow);d=ImageDraw.Draw(im)
# Entire central mark fits inside the Android maskable safe circle.
d.rounded_rectangle((276,414,748,775),radius=62,fill='#FFF3D5')
d.line([(235,444),(512,230),(789,444)],fill='#FFF3D5',width=95,joint='curve')
for x,y in [(235,444),(512,230),(789,444)]:d.ellipse((x-47,y-47,x+47,y+47),fill='#FFF3D5')
d.ellipse((366,424,658,716),fill='#603AAC')
for i,c in enumerate(['#ffc468','#ff9fc9','#b6a0ff','#91ddbd','#ffde91','#c4b2fa']):d.pieslice((386,444,638,696),i*60-90,(i+1)*60-90,fill=c)
d.ellipse((467,525,557,615),fill='#FFF9E8')
d.polygon([(504,451),(520,451),(512,477)],fill='#50316D')
# Sparkles remain secondary to the house at small sizes.
for cx,cy,r in [(762,257,46),(265,697,23),(786,696,20)]:
 d.polygon([(cx,cy-r),(cx+r*.27,cy-r*.27),(cx+r,cy),(cx+r*.27,cy+r*.27),(cx,cy+r),(cx-r*.27,cy+r*.27),(cx-r,cy),(cx-r*.27,cy-r*.27)],fill='#FFDB81')
for name,size in [('icon-192.png',192),('icon-512.png',512),('maskable-512.png',512),('apple-touch-icon.png',180)]:im.convert('RGB').resize((size,size),Image.Resampling.LANCZOS).save(root/name,optimize=True)
