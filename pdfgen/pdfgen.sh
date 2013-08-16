#!/bin/sh
# Usage pdfgen.sh output_directory input_file
echo "<p>Graham's Script</p>"
#time ls
time /usr/bin/soffice --headless --convert-to pdf --outdir $1 $2 2>&1

echo "<p>Graham's Script finished</p>"



