<!DOCTYPE html>
<html>
<head>
    <script src="https://unpkg.com/konva@9.3.6/konva.min.js"></script>
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

        #drag-items .text {
            cursor: pointer;
            padding: 5px;
            background-color: #ddd;
            display: inline-block;
            margin-right: 5px;
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

        #menu {
            display: none;
            position: absolute;
            width: 60px;
            background-color: white;
            box-shadow: 0 0 5px grey;
            border-radius: 3px;
        }

        #menu button {
            width: 100%;
            background-color: white;
            border: none;
            margin: 0;
            padding: 10px;
        }

        #menu button:hover {
            background-color: lightgray;
        }
    </style>
</head>

<body>
<p>Drag & drop an image or text into the grey area. Double click to edit text.</p>
<div id="drag-items">
    <img src="https://konvajs.org/assets/yoda.jpg" draggable="true" />
    <img src="https://konvajs.org/assets/darth-vader.jpg" draggable="true" />
    <img src="/output_images/signature.svg" draggable="true" />
    <span class="text" draggable="true">Editable Text</span>
</div>
<div id="container">
    <div id="stage-container"></div>
    <div id="stage-container-2"></div>
</div>
<button id="export-image" class="export-button">Export Image</button>
<button id="export-pdf" class="export-button">Export PDF</button>
<div id="menu">
    <div>
        <button id="delete-button">Delete</button>
    </div>
</div>
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
        if (e.target.tagName.toLowerCase() === 'img') {
            itemURL = e.target.src;
        } else if (e.target.classList.contains('text')) {
            itemURL = 'text';
            e.dataTransfer.setData('text/plain', e.target.innerText);
        }
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

        if (itemURL === 'text') {
            var text = e.dataTransfer.getData('text/plain');
            var textNode = new Konva.Text({
                x: stage.getPointerPosition().x,
                y: stage.getPointerPosition().y,
                text: text,
                fontSize: 20,
                draggable: true,
            });
            layer.add(textNode);
            layer.batchDraw();
            
            textNode.on('dblclick', function() {
                var oldText = this.text();
                this.hide();
                layer.draw();

                // create textarea over canvas with absolute position
                var areaPosition = stage.container().getBoundingClientRect();
                var textarea = document.createElement('textarea');
                textarea.value = oldText;
                textarea.style.position = 'absolute';
                textarea.style.top = (areaPosition.top + this.absolutePosition().y) + 'px';
                textarea.style.left = (areaPosition.left + this.absolutePosition().x) + 'px';
                textarea.style.width = this.width() + 'px';
                textarea.style.height = this.height() + 'px';
                textarea.style.fontSize = this.fontSize() + 'px';
                textarea.style.border = 'none';
                textarea.style.padding = '0px';
                textarea.style.margin = '0px';
                textarea.style.overflow = 'hidden';
                textarea.style.background = 'transparent';
                textarea.style.outline = 'none';
                textarea.style.resize = 'none';
                textarea.style.lineHeight = this.lineHeight();
                textarea.style.fontFamily = this.fontFamily();
                textarea.style.transformOrigin = 'left top';
                textarea.style.textAlign = this.align();
                textarea.style.color = this.fill();

                document.body.appendChild(textarea);

                textarea.focus();

                var self = this;
                textarea.addEventListener('keydown', function(e) {
                    if (e.keyCode === 13 && !e.shiftKey) {
                        self.text(textarea.value);
                        document.body.removeChild(textarea);
                        self.show();
                        layer.draw();
                    }
                });
            });

            var tr = new Konva.Transformer();
            layer.add(tr);
            tr.attachTo(textNode);

        } else {
            Konva.Image.fromURL(itemURL, function (image) {
                layer.add(image);

                image.position(stage.getPointerPosition());
                image.draggable(true);

                var imagePos = image.position();
                var imageWidth = image.width();
                var imageHeight = image.height();

                // Add Transformer to the image
                var tr = new Konva.Transformer();
                layer.add(tr);
                tr.attachTo(image);

                layer.batchDraw();
            });
        }
    });

    document.getElementById('export-image').addEventListener('click', function () {
    // Hide Transformer before exporting
    var tr = layer.findOne('Transformer');
    tr.hide();

    // Export image
    var dataURL = stage.toDataURL({ pixelRatio: 3 });

    // Show Transformer again after exporting
    tr.show();

    // Create a link and trigger download
    var link = document.createElement('a');
    link.href = dataURL;
    link.download = 'image.png';
    link.click();
});

document.getElementById('export-pdf').addEventListener('click', function () {
    // Hide Transformer before exporting
    var tr = layer.findOne('Transformer');
    tr.hide();

    var pdf = new jspdf.jsPDF();
    pdf.addImage(stage.toDataURL(), 'JPEG', 0, 0);
    pdf.save('stage.pdf');

    // Show Transformer again after exporting
    tr.show();
});

var menuNode = document.getElementById('menu');
document.getElementById('delete-button').addEventListener('click', () => {
    if (currentShape) {
        // Find and remove transformer associated with the current shape
        var tr = layer.find((node) => node.getClassName() === 'Transformer' && node.node() === currentShape)[0];
        if (tr) {
            tr.destroy();
        }
        currentShape.destroy();
        layer.draw();
        menuNode.style.display = 'none';
    }
});

window.addEventListener('click', () => {
    // hide menu
    menuNode.style.display = 'none';
});

var currentShape;
stage.on('contextmenu', function (e) {
    // prevent default behavior
    e.evt.preventDefault();
    if (e.target === stage) {
        // if we are on empty place of the stage we will do nothing
        return;
    }
    currentShape = e.target;
    // show menu
    menuNode.style.display = 'initial';
    var containerRect = stage.container().getBoundingClientRect();
    menuNode.style.top =
        containerRect.top + stage.getPointerPosition().y + 4 + 'px';
    menuNode.style.left =
        containerRect.left + stage.getPointerPosition().x + 4 + 'px';
});
</script>
</body>
</html>
