<!DOCTYPE html>
<html>
<head>
    <script src="https://unpkg.com/konva@9.3.5/konva.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <meta charset="utf-8" />
    <title>Konva Drop DOM element Demo</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: #f0f0f0;
        }

        #container {
            background-color: rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            position: relative; /* Ensure correct positioning of export buttons */
        }

        p {
            margin: 4px;
        }

        #drag-items img {
            height: 100px;
        }

        .resizeHandle {
            display: none; /* Hide blue dots */
        }

        .export-button {
            position: absolute;
            top: 10px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .export-button:hover {
            background-color: #0056b3;
        }

        #export-image {
            right: 10px;
        }

        #export-pdf {
            right: 150px;
        }
    </style>
</head>

<body>
<p>Drag & drop an image into the grey area. Double click to enlarge.</p>
<div id="drag-items">
    <img src="https://konvajs.org/assets/yoda.jpg" draggable="true" />
    <img src="https://konvajs.org/assets/darth-vader.jpg" draggable="true" />
    <img src="/output_images/signature.svg" draggable="true" />
</div>
<div id="container">
    <div id="stage-container"></div>
</div>
<button id="export-image" class="export-button">Export Image</button>
<button id="export-pdf" class="export-button">Export PDF</button>
<script>
    var stageWidth = 0;
    var stageHeight = 0;

    var stageContainer = document.getElementById('stage-container');

    // Create stage with a fixed size
    var stage = new Konva.Stage({
        container: 'stage-container',
        width: stageWidth,
        height: stageHeight,
    });
    var layer = new Konva.Layer();
    stage.add(layer);

    // Background image
    var backgroundImage = new Image();
    backgroundImage.src = '/output_images/testimg.png';
    backgroundImage.onload = function() {
        stageWidth = this.width;
        stageHeight = this.height;

        var bgImage = new Konva.Image({
            image: backgroundImage,
            width: stageWidth,
            height: stageHeight,
        });
        layer.add(bgImage);
        layer.batchDraw(); // Redraw the layer

        // Update stage dimensions
        stage.width(stageWidth);
        stage.height(stageHeight);
    };

    // what is url of dragging element?
    var itemURL = '';
    document.getElementById('drag-items').addEventListener('dragstart', function (e) {
        itemURL = e.target.src;
    });

    var con = stage.container();
    con.addEventListener('dragover', function (e) {
        e.preventDefault(); // !important
    });

    con.addEventListener('drop', function (e) {
        e.preventDefault();
        // now we need to find pointer position
        // we can't use stage.getPointerPosition() here, because that event
        // is not registered by Konva.Stage
        // we can register it manually:
        stage.setPointersPositions(e);

        Konva.Image.fromURL(itemURL, function (image) {
            layer.add(image);

            image.position(stage.getPointerPosition());
            image.draggable(true);

            var imagePos = image.position();
            var imageWidth = image.width();
            var imageHeight = image.height();

            // Add event listeners to the image for resizing
            var topLeft = new Konva.Rect({
                x: imagePos.x - 5,
                y: imagePos.y - 5,
                width: 10,
                height: 10,
                fill: 'blue', // Transparent
                draggable: true,
                name: 'topLeft',
                className: 'resizeHandle' // Set the class name here
            });

            var topRight = new Konva.Rect({
                x: imagePos.x + imageWidth - 5,
                y: imagePos.y - 5,
                width: 10,
                height: 10,
                fill: 'blue', // Transparent
                draggable: true,
                name: 'topRight',
                className: 'resizeHandle' // Set the class name here
            });

            var bottomLeft = new Konva.Rect({
                x: imagePos.x - 5,
                y: imagePos.y + imageHeight - 5,
                width: 10,
                height: 10,
                fill: 'blue', // Transparent
                draggable: true,
                name: 'bottomLeft',
                className: 'resizeHandle' // Set the class name here
            });

            var bottomRight = new Konva.Rect({
                x: imagePos.x + imageWidth - 5,
                y: imagePos.y + imageHeight - 5,
                width: 10,
                height: 10,
                fill: 'blue', // Transparent
                draggable: true,
                name: 'bottomRight',
                className: 'resizeHandle' // Set the class name here
            });

            layer.add(topLeft);
            layer.add(topRight);
            layer.add(bottomLeft);
            layer.add(bottomRight);

            function updateResizeHandles() {
                var imagePos = image.position();
                var imageWidth = image.width();
                var imageHeight = image.height();

                topLeft.position({
                    x: imagePos.x - 5,
                    y: imagePos.y - 5,
                });
                topRight.position({
                    x: imagePos.x + imageWidth - 5,
                    y: imagePos.y - 5,
                });
                bottomLeft.position({
                    x: imagePos.x - 5,
                    y: imagePos.y + imageHeight - 5,
                });
                bottomRight.position({
                    x: imagePos.x + imageWidth - 5,
                    y: imagePos.y + imageHeight - 5,
                });
            }

            updateResizeHandles();

            image.on('dragmove', function () {
                updateResizeHandles();
                layer.batchDraw();
            });

            topLeft.on('dragmove', function () {
                var pos = topLeft.position();
                var newWidth = imageWidth - (pos.x - imagePos.x);
                var newHeight = imageHeight - (pos.y - imagePos.y);
                image.setAttrs({
                    width: newWidth,
                    height: newHeight,
                    x: pos.x + 5,
                    y: pos.y + 5,
                });
                updateResizeHandles();
                layer.batchDraw();
            });

            topRight.on('dragmove', function () {
                var pos = topRight.position();
                var newWidth = pos.x - imagePos.x + 5;
                var newHeight = imageHeight - (pos.y - imagePos.y);
                image            .setAttrs({
                width: newWidth,
                height: newHeight,
                y: pos.y + 5,
            });
            updateResizeHandles();
            layer.batchDraw();
        });

        bottomLeft.on('dragmove', function () {
            var pos = bottomLeft.position();
            var newWidth = imageWidth - (pos.x - imagePos.x);
            var newHeight = pos.y - imagePos.y + 5;
            image.setAttrs({
                width: newWidth,
                height: newHeight,
                x: pos.x + 5,
            });
            updateResizeHandles();
            layer.batchDraw();
        });

        bottomRight.on('dragmove', function () {
            var pos = bottomRight.position();
            var newWidth = pos.x - imagePos.x + 5;
            var newHeight = pos.y - imagePos.y + 5;
            image.setAttrs({
                width: newWidth,
                height: newHeight,
            });
            updateResizeHandles();
            layer.batchDraw();
        });

        var bgImage = new Konva.Image({
            image: backgroundImage,
            width: stageWidth,
            height: stageHeight
        });

        // Ensure that background image cannot be dragged or resized
        bgImage.draggable(false);
        bgImage.resizable(false);

        // Add background image to the layer
        layer.add(bgImage);

        // Center the stage
        stageContainer.style.display = 'flex';
        stageContainer.style.justifyContent = 'center';
        stageContainer.style.alignItems = 'center';

        // Redraw the layer
        layer.batchDraw();
    });
});

document.getElementById('export-image').addEventListener('click', function () {
    // Hide blue dots before exporting
    var dots = layer.find('.resizeHandle');
    dots.forEach(function (dot) {
        dot.hide();
        
    });


    // Export image
    var dataURL = stage.toDataURL({ pixelRatio: 3 });

    // Show blue dots again after exporting
    dots.forEach(function (dot) {
        dot.show();
    });

    // Create a link and trigger download
    var link = document.createElement('a');
    link.href = dataURL;
    link.download = 'image.png';
    link.click();
});

document.getElementById('export-pdf').addEventListener('click', function () {
    // Hide blue dots before exporting
    var dots = layer.find('.resizeHandle');
    dots.forEach(function (dot) {
        dot.opacity(0); // Set opacity to 0 to make the dots transparent
    });

    var pdf = new jspdf.jsPDF();
    pdf.addImage(stage.toDataURL(), 'JPEG', 0, 0);
    pdf.save('stage.pdf');

    // Show blue dots again after exporting
    dots.forEach(function (dot) {
        dot.show();
    });
});
</script>
</body>
</html>

