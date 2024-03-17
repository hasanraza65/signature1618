<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Viewer</title>
    <style>
        body {
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f0f0f0;
        }

        #pdfContainer {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            align-items: center;
            max-width: 90vw; /* Limit container width */
            position: relative; /* Relative positioning for absolute elements */
        }

        canvas {
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
            max-width: 100%; /* Limit canvas width */
            height: auto; /* Maintain aspect ratio */
            position: relative; /* Relative positioning for absolute elements */
        }

        #toolbox {
            margin-top: 20px;
        }

        .pdf-page {
            position: relative;
        }

        .pdf-image {
            position: absolute;
            pointer-events: none; /* Prevent interaction with image */
            transition: transform 0.2s ease-out; /* Smooth transition */
        }
    </style>
</head>
<body>
    <input type="file" id="fileInput" accept=".pdf">
    <div id="pdfContainer"></div>

    <div id="toolbox">
        <input type="file" id="imageInput" accept="image/*">
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.min.js"></script>
    <script>
        // Initialize PDF.js
        const pdfjsLib = window['pdfjs-dist/build/pdf'];

        // Function to render PDF pages
        function renderPDF(pdf) {
            const container = document.getElementById('pdfContainer');

            // Loop through each page of the PDF document
            for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                // Create a container div for each page
                const pageContainer = document.createElement('div');
                pageContainer.classList.add('pdf-page');
                container.appendChild(pageContainer);

                // Create a canvas element for each page
                const canvas = document.createElement('canvas');
                pageContainer.appendChild(canvas);

                // Fetch the page
                pdf.getPage(pageNum).then(page => {
                    const viewport = page.getViewport({ scale: 1 });
                    const canvasContext = canvas.getContext('2d');

                    // Set canvas dimensions
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;

                    // Render PDF page into canvas context
                    const renderContext = {
                        canvasContext,
                        viewport
                    };
                    page.render(renderContext);
                });
            }
        }

        // Handle file input change event for PDF file
        document.getElementById('fileInput').addEventListener('change', function(event) {
            const file = event.target.files[0];

            // Check if a file is selected
            if (file) {
                const fileReader = new FileReader();

                // Read the file as ArrayBuffer
                fileReader.readAsArrayBuffer(file);

                // When file is loaded, open the PDF
                fileReader.onload = function() {
                    const container = document.getElementById('pdfContainer');
                    container.innerHTML = ''; // Clear previous content

                    const loadingTask = pdfjsLib.getDocument({ data: this.result });
                    loadingTask.promise.then(pdf => {
                        renderPDF(pdf);
                    }).catch(error => {
                        console.error('Error loading PDF:', error);
                    });
                };
            }
        });

        // Handle file input change event for image file
        document.getElementById('imageInput').addEventListener('change', function(event) {
            const file = event.target.files[0];

            // Check if a file is selected
            if (file) {
                // Create a new image element
                const image = document.createElement('img');
                image.classList.add('pdf-image');
                image.style.maxWidth = '100px'; // Set initial width for the image
                image.style.height = 'auto'; // Maintain aspect ratio

                // Set the image source to the selected file
                const reader = new FileReader();
                reader.onload = function(event) {
                    image.src = event.target.result;

                    // Append the image to the PDF container
                    const container = document.getElementById('pdfContainer');
                    container.appendChild(image);

                    // Handle drag event for the image
                    let offsetX, offsetY;
                    let isDragging = false;
                    image.addEventListener('mousedown', startDrag);
                    document.addEventListener('mousemove', drag);
                    document.addEventListener('mouseup', endDrag);

                    function startDrag(e){
    e.preventDefault();
    isDragging = true;
    const rect = image.getBoundingClientRect();
    offsetX = e.clientX - rect.left;
    offsetY = e.clientY - rect.top;
}

function drag(e) {
    if (isDragging) {
        e.preventDefault();
        const x = e.clientX - offsetX;
        const y = e.clientY - offsetY;
        image.style.left = x + 'px';
        image.style.top = y + 'px';
    }
}

function endDrag(e) {
    e.preventDefault();
    isDragging = false;
}

}
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
