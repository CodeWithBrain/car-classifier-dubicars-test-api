<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dubicars Classify API Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .upload-section {
            border: 2px dashed #ccc;
            padding: 40px;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 20px;
            background-color: #fafafa;
        }
        .upload-section.dragover {
            border-color: #007bff;
            background-color: #e3f2fd;
        }
        #fileInput {
            display: none;
        }
        .upload-btn {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px;
        }
        .upload-btn:hover {
            background-color: #0056b3;
        }
        .preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
        }
        .preview img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
            border: 2px solid #ddd;
        }
        .submit-btn {
            background-color: #28a745;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            width: 100%;
            margin-top: 20px;
        }
        .submit-btn:hover {
            background-color: #218838;
        }
        .submit-btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        .result {
            margin-top: 30px;
            padding: 20px;
            border-radius: 5px;
            display: none;
        }
        .result.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .result.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .loading {
            text-align: center;
            color: #666;
        }
        .car-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .car-details h3 {
            margin-top: 0;
            color: #333;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        .detail-value {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚗 Car Classifier API Test</h1>
        
        <div class="upload-section" id="uploadSection">
            <h3>Upload Car Images</h3>
            <p>Drag and drop images here or click to select files</p>
            <input type="file" id="fileInput" multiple accept="image/*">
            <button class="upload-btn" onclick="document.getElementById('fileInput').click()">
                Choose Files
            </button>
        </div>
        
        <div class="preview" id="preview"></div>
        
        <button class="submit-btn" id="submitBtn" onclick="classifyImages()" disabled>
            Classify Images
        </button>
        
        <div class="result" id="result"></div>
    </div>

    <script>
        const uploadSection = document.getElementById('uploadSection');
        const fileInput = document.getElementById('fileInput');
        const preview = document.getElementById('preview');
        const submitBtn = document.getElementById('submitBtn');
        const result = document.getElementById('result');
        
        let selectedFiles = [];

        // Drag and drop functionality
        uploadSection.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadSection.classList.add('dragover');
        });

        uploadSection.addEventListener('dragleave', () => {
            uploadSection.classList.remove('dragover');
        });

        uploadSection.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadSection.classList.remove('dragover');
            const files = Array.from(e.dataTransfer.files);
            handleFiles(files);
        });

        fileInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            handleFiles(files);
        });

        function handleFiles(files) {
            const imageFiles = files.filter(file => file.type.startsWith('image/'));
            selectedFiles = imageFiles;
            
            preview.innerHTML = '';
            imageFiles.forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.title = file.name;
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
            
            submitBtn.disabled = imageFiles.length === 0;
        }

        async function classifyImages() {
            if (selectedFiles.length === 0) return;

            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
            result.style.display = 'none';

            const formData = new FormData();
            selectedFiles.forEach(file => {
                formData.append('images[]', file);
            });

            try {
                const response = await fetch('/api/classify', {
                    method: 'POST',
                    body: formData
                });

                let data;
                try {
                    data = await response.json();
                } catch (jsonError) {
                    // If response is not valid JSON, show a clear error
                    const text = await response.text();
                    displayResult({ error: 'Invalid JSON response from server.\n' + text }, false);
                    return;
                }

                if (response.ok && data.success) {
                    displayResult(data, true);
                } else {
                    displayResult(data, false);
                }
            } catch (error) {
                displayResult({ error: 'Network error: ' + error.message }, false);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Classify Images';
            }
        }

        function displayResult(data, isSuccess) {
            result.className = `result ${isSuccess ? 'success' : 'error'}`;
            result.style.display = 'block';

            if (isSuccess) {
                let html = `
                    <h3>✅ Classification Results</h3>
                    <p><strong>Images processed:</strong> ${data.images_processed}</p>
                    
                    <div class="car-details">
                        <h3>Car Details</h3>
                `;

                Object.entries(data.car_details).forEach(([key, value]) => {
                    const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    html += `
                        <div class="detail-row">
                            <span class="detail-label">${label}:</span>
                            <span class="detail-value">${value}</span>
                        </div>
                    `;
                });

                html += `
                    </div>
                `;

                result.innerHTML = html;
            } else {
                result.innerHTML = `
                    <h3>❌ Error</h3>
                    <p>${data.error}</p>
                    ${data.usage ? `<pre>${JSON.stringify(data.usage, null, 2)}</pre>` : ''}
                `;
            }
        }
    </script>
</body>
</html> 