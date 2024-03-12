<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\PdfToImage\Pdf;
use Imagick;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ManagePDFController extends Controller
{
    public function convertToPng(Request $request)
    {
        // Validate the incoming request
        /*
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:2048', // Adjust max file size if needed
        ]); */

        // Get the PDF file from the request
        $pdfFile = $request->file('pdf');

        // Create the directory if it doesn't exist
        $directory = public_path('pdf_images');
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        // Initialize a new Pdf object
        $pdf = new Pdf($pdfFile->getRealPath());

        // Initialize an array to store image paths
        $imagePaths = [];

        // Loop through each page and convert it to an image
        for ($pageNumber = 1; $pageNumber <= $pdf->getNumberOfPages(); $pageNumber++) {
            // Create a unique filename for each image
            $imageName = uniqid('pdf_image_') . '_' . $pageNumber . '.png'; // Change extension to PNG
            
            // Path to save the converted image
            $imagePath = public_path('pdf_images/' . $imageName);

            // Set resolution and output format
            $pdf->setResolution(300) // Set DPI to 300 (adjust as needed)
                ->setOutputFormat('png'); // Set output format to PNG


            // Convert the current page of the PDF to an image
            $pdf->setPage($pageNumber)->saveImage($imagePath);

            // Fill the background of the image with white color
            $image = imagecreatefrompng($imagePath);
            $whiteColor = imagecolorallocate($image, 255, 255, 255);
            imagefill($image, 0, 0, $whiteColor);
            imagepng($image, $imagePath);

            // Add the image path to the array
            //$imagePaths[] = asset('pdf_images/' . $imageName);
            $imagePaths[] = "pdf_images/". $imageName;
        }

        // Return the paths to the converted images
        return response()->json([
            'image_paths' => $imagePaths
        ]);
    }

    public function addImageElement(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'main_image' => 'required|image|mimes:png|max:2048', // Adjust max file size if needed
            'element_image' => 'required|image|mimes:png|max:2048', // Adjust max file size if needed
            'x_position' => 'required|integer',
            'y_position' => 'required|integer',
            'element_width' => 'integer',
            'element_height' => 'integer',
        ]);

        // Get the main PNG image and element image from the request
        $mainImage = $request->file('main_image');
        $elementImage = $request->file('element_image');

        // Open the main image using GD
        $image = imagecreatefrompng($mainImage->getRealPath());

        // Open the element image using GD
        $element = imagecreatefrompng($elementImage->getRealPath());

        // Get the dimensions of the element image
        $elementWidth = $request->input('element_width', imagesx($element));
        $elementHeight = $request->input('element_height', imagesy($element));

        // Resize the element image if necessary
        if ($elementWidth !== imagesx($element) || $elementHeight !== imagesy($element)) {
            $resizedElement = imagecreatetruecolor($elementWidth, $elementHeight);
            imagealphablending($resizedElement, false);
            imagesavealpha($resizedElement, true);
            $transparent = imagecolorallocatealpha($resizedElement, 0, 0, 0, 127);
            imagefill($resizedElement, 0, 0, $transparent);
            imagecopyresampled($resizedElement, $element, 0, 0, 0, 0, $elementWidth, $elementHeight, imagesx($element), imagesy($element));
            imagedestroy($element);
            $element = $resizedElement;
        }

        // Get the position to place the element image
        $xPosition = $request->input('x_position');
        $yPosition = $request->input('y_position');

        // Place the element image onto the main image at the specified position
        imagecopy($image, $element, $xPosition, $yPosition, 0, 0, $elementWidth, $elementHeight);

        // Define the directory path
        $outputDirectory = public_path('output_images');

        // Check if the directory exists, if not, create it
        if (!File::isDirectory($outputDirectory)) {
            File::makeDirectory($outputDirectory, 0777, true, true);
        }

        // Generate a unique name for the saved image
        $imageName = 'annotated_image_' . Str::random(10) . '.png';

        // Save the modified image with the unique name
        $outputImagePath = $outputDirectory . '/' . $imageName;
        imagepng($image, $outputImagePath);

        // Free up memory
        imagedestroy($image);
        imagedestroy($element);

        // Return the path to the annotated image
        return response()->json([
            'annotated_image_path' => asset('output_images/' . $imageName)
        ]);
    }

    public function testConvert(Request $request){

        $pdfFile = $request->file('pdf');
        $directory = public_path('pdf_images');
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        $pdf = new Pdf($pdfFile->getRealPath());
        $imagePaths = [];

        for ($pageNumber = 1; $pageNumber <= $pdf->getNumberOfPages(); $pageNumber++) {
            $imageName = 'pdf_image_' . $pageNumber . '.png';
            $imagePath = $directory . '/' . $imageName;
            $pdf->setPage($pageNumber)->saveImage($imagePath);
            $imagePaths[] = "pdf_images/" . $imageName;
        }

        return $imagePaths;

    }

}
