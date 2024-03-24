<html>
    <head>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">


    <style>
        body, html {
    height: 100%;
    overflow: hidden;
    }

    body {
        background-color: #3a3d55;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Roboto', sans-serif;
    }

    #button-background {
        position: relative;
        background-color: rgba(255,255,255,0.3);
        width: 400px;
        height: 80px;
        border-radius: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #slider {
        transition: width 0.3s, border-radius 0.3s, height 0.3s;
        position: absolute;
        left: -10px;
        background-color: white;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #slider.unlocked {
        transition: all 0.3s;
        width: inherit;
        left: 0 !important;
        height: inherit;
        border-radius: inherit;
    }

    .material-icons {
        color: black;
        font-size: 50px;
        user-select: none;
        cursor: default;
    }

    .slide-text {
        color: #3a3d55;
        font-size: 24px;
        text-transform: uppercase;
        user-select: none;
        cursor: default;
    }

    .bottom {
        position: fixed;
        bottom: 0;
        font-size: 14px;
        color: white;
    }

    .bottom a {
        color: white;
    }

    #myImage {
        opacity: 0.3; /* Initial low opacity */
        transition: opacity 0.3s; /* Smooth transition for opacity change */
    }

    </style>

        <body>

    <img id="myImage" src="profile_images/1709578897.png" alt="Your Image">
   

        <div id="button-background">
        <span class="slide-text">Swipe</span>
        <div id="slider">
            <i id="locker" class="material-icons">lock_open</i>
        </div>
    </div>

    



<script>
    var initialMouse = 0;
var slideMovementTotal = 0;
var mouseIsDown = false;
var slider = document.getElementById('slider');

slider.addEventListener('mousedown', handleMouseDown);
slider.addEventListener('touchstart', handleMouseDown);

document.addEventListener('mouseup', handleMouseUp);
document.addEventListener('touchend', handleMouseUp);

document.addEventListener('mousemove', handleMouseMove);
document.addEventListener('touchmove', handleMouseMove);

function handleMouseDown(event) {
    mouseIsDown = true;
    slideMovementTotal = document.getElementById('button-background').offsetWidth - slider.offsetWidth + 10;
    initialMouse = event.clientX || event.touches[0].pageX;
}

function handleMouseUp(event) {
    if (!mouseIsDown) return;
    mouseIsDown = false;
    var currentMouse = event.clientX || event.changedTouches[0].pageX;
    var relativeMouse = currentMouse - initialMouse;

    if (relativeMouse < slideMovementTotal) {
        document.querySelector('.slide-text').style.opacity = 1;
        slider.style.left = "-10px";
        return;
    }
    slider.classList.add('unlocked');
    document.getElementById('locker').textContent = 'lock_outline';
    setTimeout(function() {
        slider.addEventListener('click', handleSliderClick);
    }, 0);
}

function handleMouseMove(event) {
    if (!mouseIsDown) return;
    var currentMouse = event.clientX || event.touches[0].pageX;
    var relativeMouse = currentMouse - initialMouse;
    var slidePercent = 1 - (relativeMouse / slideMovementTotal);
    document.querySelector('.slide-text').style.opacity = slidePercent;

    if (relativeMouse <= 0) {
        slider.style.left = '-10px';
        return;
    }
    if (relativeMouse >= slideMovementTotal + 10) {
        slider.style.left = slideMovementTotal + 'px';
        return;
    }
    slider.style.left = relativeMouse - 10 + 'px';
}

function handleSliderClick(event) {
    if (!slider.classList.contains('unlocked')) return;
    slider.classList.remove('unlocked');
    document.getElementById('locker').innerHTML = 'lock_open';
    document.getElementById('myImage').style.opacity = 1; // Change image opacity to fully visible
    slider.removeEventListener('click', handleSliderClick);
}

function handleMouseDown(event) {
    mouseIsDown = true;
    slideMovementTotal = document.getElementById('button-background').offsetWidth - slider.offsetWidth + 10;
    initialMouse = event.clientX || event.touches[0].pageX;
}

function handleMouseMove(event) {
    if (!mouseIsDown) return;
    var currentMouse = event.clientX || event.touches[0].pageX;
    var relativeMouse = currentMouse - initialMouse;
    var slidePercent = 1 - (relativeMouse / slideMovementTotal);
    document.querySelector('.slide-text').style.opacity = slidePercent;

    if (relativeMouse <= 0) {
        slider.style.left = '-10px';
        return;
    }
    if (relativeMouse >= slideMovementTotal + 10) {
        slider.style.left = slideMovementTotal + 'px';
        return;
    }
    slider.style.left = relativeMouse - 10 + 'px';

    // Adjust the opacity of the image dynamically based on the slide percentage
    var imageOpacity = 1 - slidePercent;
    if (imageOpacity < 0.3) {
        imageOpacity = 0.3; // Set a minimum opacity value
    }
    document.getElementById('myImage').style.opacity = imageOpacity;
}

</script>

        </body>
    </head>
</html>