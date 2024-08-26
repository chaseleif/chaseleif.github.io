#! /usr/bin/env python3

import os
from datetime import datetime

xmlver = '<?xml version=\"1.0\" encoding=\"UTF-8\"?>'
urlset = '<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">'
url = 'https://chaseleif.tech'

plus = datetime.fromtimestamp(os.path.getmtime('index.php'))
plus = plus.astimezone().isoformat().split('-')[-1]
plus = int(plus.split(':')[0])*3600

ts = lambda f: \
  datetime.fromtimestamp(round(os.path.getmtime(f))+plus).isoformat()+'+00:00'

#def ts(f):
#  ts = datetime.fromtimestamp(round(os.path.getmtime(f))+plus).isoformat()
#  return ts+'+00:00'

with open('robots.txt','w') as outfile:
  outfile.write('User-agent: *\n')
  outfile.write('Disallow: /index.php?resume\n')
  outfile.write('Disallow: /images\n')
  outfile.write('Disallow: /pages\n\n')
  outfile.write(f'Sitemap: {url}/sitemap.xml\n')

with open('sitemap.xml','w') as outfile:
  outfile.write(f'{xmlver}\n')
  outfile.write(f'{urlset}\n')
  mod = round(os.path.getmtime('index.php'))
  if mod >= round(os.path.getmtime('page.html')):
    f = 'index.php'
  else:
    f = 'page.html'
  for loc in (f'{url}/',f'{url}/index.php'):
    outfile.write('<url>\n')
    outfile.write(f'  <loc>{loc}</loc>\n')
    outfile.write(f'  <lastmod>{ts(f)}</lastmod>\n')
    outfile.write('</url>\n')
  for page in os.listdir('pages'):
    if page == 'resume.html' or page == 'index.html': continue
    f = f'pages/{page}'
    page = page.split('.')[0]
    outfile.write('<url>\n')
    outfile.write(f'  <loc>{url}/index.php?{page}</loc>\n')
    outfile.write(f'  <lastmod>{ts(f)}</lastmod>\n')
    outfile.write('</url>\n')
  outfile.write('</urlset>\n')
