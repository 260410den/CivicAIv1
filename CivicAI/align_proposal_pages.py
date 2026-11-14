from docx import Document
from docx.enum.text import WD_BREAK
from pathlib import Path

root = Path(__file__).parent
source = root / 'Proposal_CivicAI_Sesuai_Template.docx'
target = root / 'Proposal_CivicAI_Sesuai_Template_Final.docx'
doc = Document(source)

# Insert explicit breaks only before the sections whose template pages are fixed.
for heading in ['3.5 ERD (Entity Relationship Diagram)', '3.6 Design Sistem']:
    for paragraph in doc.paragraphs:
        if paragraph.text.strip().startswith(heading):
            previous = paragraph._p.getprevious()
            has_break = False
            if previous is not None:
                has_break = 'w:type="page"' in previous.xml or '<w:br/>' in previous.xml
            if not has_break:
                paragraph.insert_paragraph_before().add_run().add_break(WD_BREAK.PAGE)
            break

# Keep the table of contents aligned with the reference structure.
replacements = {
    'Gambar 1. Flowchart Sistem CivicAI': 'Gambar 3.1 Flowchart Sistem CivicAI',
    'Gambar 2. ERD Konseptual CivicAI': 'Gambar 3.2 ERD Konseptual CivicAI',
}
for paragraph in doc.paragraphs:
    for old, new in replacements.items():
        if old in paragraph.text:
            for run in paragraph.runs:
                run.text = run.text.replace(old, new)

# Compact placeholder cells so the design section stays within its assigned page.
for table in doc.tables:
    for row in table.rows:
        for cell in row.cells:
            if '(Taro foto sendiri)' in cell.text:
                for paragraph in cell.paragraphs:
                    paragraph.paragraph_format.space_before = 0
                    paragraph.paragraph_format.space_after = 0
                    for run in paragraph.runs:
                        run.font.size = run.font.size or __import__('docx').shared.Pt(10)

doc.save(target)
print(target)
