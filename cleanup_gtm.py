import re, glob

pattern = re.compile(
    r'\s*<!-- Google Tag Manager-->\s*\n\s*<script>\s*\n.*?GTM-WKV3GT5.*?\n\s*</script>\s*\n',
    re.DOTALL
)
noscript_pattern = re.compile(
    r'\s*<!-- Google Tag Manager \(noscript\)-->\s*\n\s*<noscript>\s*\n\s*<iframe src="http://www\.googletagmanager\.com/ns\.html\?id=GTM-WKV3GT5".*?</iframe>\s*\n\s*</noscript>\s*\n',
    re.DOTALL
)

files = glob.glob('public/components/*.html')
fixed = 0
for f in files:
    with open(f, 'r') as fh:
        content = fh.read()
    new_content = pattern.sub('\n', content)
    new_content = noscript_pattern.sub('\n', new_content)
    if new_content != content:
        with open(f, 'w') as fh:
            fh.write(new_content)
        fixed += 1
print(f'Fixed {fixed} files')
