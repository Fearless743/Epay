# Taste (Continuously Learned by [CommandCode][cmd])

[cmd]: https://commandcode.ai/

# workflow
- When edit_file fails repeatedly on legacy PHP files due to whitespace/encoding issues, fall back to write_file with the full file content instead. Confidence: 0.60
- When user says "推送更新", execute the full release workflow from CLAUDE.md: bump VERSION in includes/common.php → commit → git tag v{VERSION} → git push origin main --tags. This is a formal release, not a regular push. Confidence: 0.75
