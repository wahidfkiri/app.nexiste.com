import os

path = 'resources/views/dashboard.blade.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

# CSS variables
content = content.replace('--gold: #c9923a;', '--blue-main: #2563eb;')
content = content.replace('--gold-light:', '--blue-light:')
content = content.replace('--gold-pale:', '--blue-pale:')
content = content.replace('--gold-dim:', '--blue-dim-new:')
content = content.replace('--gold-glow:', '--blue-glow:')
content = content.replace('--border-gold:', '--border-blue:')

# usages
content = content.replace('var(--gold)', 'var(--blue)')
content = content.replace('var(--gold-light)', 'var(--blue-light)')
content = content.replace('var(--gold-pale)', 'var(--blue-pale)')
content = content.replace('var(--gold-dim)', 'var(--blue-dim-new)')
content = content.replace('var(--gold-glow)', 'var(--blue-glow)')
content = content.replace('var(--border-gold)', 'var(--border-blue)')

content = content.replace('rgba(201,146,58', 'rgba(37,99,235')
content = content.replace('rgba(201, 146, 58', 'rgba(37, 99, 235')
content = content.replace('#c9923a', '#2563eb')
content = content.replace("'stat_icon_classes', ['gold'", "'stat_icon_classes', ['blue'")
content = content.replace('--blue-main: #2563eb;', '--blue: #2563eb;')
content = content.replace('.stat-icon.gold { background:var(--blue-dim-new); color:var(--blue); }', '.stat-icon.gold { background:rgba(201,146,58,.15); color:#c9923a; }')

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Replacement complete.")
