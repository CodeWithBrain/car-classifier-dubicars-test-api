# 🚗 Car Classifier API

A PHP-based API that uses OpenAI's GPT-4o to classify car images and extract detailed information about vehicles.

## Features

- 📸 **Multiple Image Upload**: Support for uploading multiple car images
- 🤖 **AI-Powered Classification**: Uses OpenAI GPT-4o for accurate car analysis
- 📊 **Detailed Car Information**: Extracts make, model, color, transmission, and more
- 📝 **SEO Description**: Automatically generates SEO-friendly car descriptions
- 🌐 **RESTful API**: Clean JSON responses with proper error handling
- 🎨 **Web Interface**: Built-in HTML test page for easy testing

## Car Details Extracted

- **Make**: Car manufacturer (e.g., Toyota, BMW)
- **Model**: Specific model name (e.g., Camry, X5)
- **Color**: Exterior color
- **Interior Color**: Interior upholstery color
- **Cylinders**: Engine cylinder count
- **Transmission**: Transmission type (Automatic/Manual)
- **Steering Side**: Left-hand or right-hand drive
- **Vehicle Type**: Sedan, SUV, Truck, etc.
- **Number of Doors**: 2, 4, 5 doors
- **Seating Capacity**: Number of seats
- **Wheel Size**: Wheel diameter
- **Fuel Type**: Gasoline, Diesel, Electric, etc.

## Quick Start

### Prerequisites

- PHP 8.0 or higher
- Composer
- OpenAI API key

### Local Setup

1. **Clone the repository**
   ```bash
   git clone <your-repo-url>
   cd car-classifier-dubicars-test-api
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Set up environment variables**
   ```bash
   cp env.example .env
   ```
   Edit `.env` and add your OpenAI API key:
   ```
   OPENAI_API_KEY=your_openai_api_key_here
   ```

4. **Start the development server**
   ```bash
   composer start
   ```
   Or manually:
   ```bash
   php -S localhost:8000 -t public
   ```

5. **Test the API**
   - Open `http://localhost:8000/test.html` in your browser
   - Upload car images and see the classification results

## API Documentation

### Endpoint: `POST /api/classify`

Classifies car images and returns detailed information.

#### Request Methods

**Method 1: File Upload (multipart/form-data)**
```bash
curl -X POST http://localhost:8000/api/classify \
  -F "images[]=@car1.jpg" \
  -F "images[]=@car2.jpg"
```

**Method 2: JSON with Image URLs**
```bash
curl -X POST http://localhost:8000/api/classify \
  -H "Content-Type: application/json" \
  -d '{
    "images": [
      "https://example.com/car1.jpg",
      "https://example.com/car2.jpg"
    ]
  }'
```

#### Response Format

**Success Response (200)**
```json
{
  "success": true,
  "car_details": {
    "make": "Toyota",
    "model": "Camry",
    "color": "Silver",
    "interior_color": "Black",
    "cylinders": "4",
    "transmission": "Automatic",
    "steering_side": "Left",
    "vehicle_type": "Sedan",
    "number_of_doors": "4",
    "seating_capacity": "5",
    "wheel_size": "17 inch",
    "fuel_type": "Gasoline"
  },
  "seo_description": "This stunning Toyota Camry sedan features a sleek silver exterior with a sophisticated black interior. With its reliable 4-cylinder engine and smooth automatic transmission, this 4-door vehicle comfortably seats 5 passengers and comes equipped with 17-inch wheels for a smooth ride.",
  "images_processed": 2
}
```

**Error Response (400/500)**
```json
{
  "error": "Error message here",
  "usage": {
    "method": "POST",
    "content_type": "multipart/form-data",
    "field_name": "images[]",
    "or": "JSON body with 'images' array of URLs"
  }
}
```

## Deployment to Render

### Option 1: Using Render Dashboard

1. **Connect your GitHub repository** to Render
2. **Create a new Web Service**
3. **Configure the service**:
   - **Environment**: PHP
   - **Build Command**: `composer install`
   - **Start Command**: `php -S 0.0.0.0:$PORT -t public`
4. **Add Environment Variable**:
   - Key: `OPENAI_API_KEY`
   - Value: Your OpenAI API key
5. **Deploy**

### Option 2: Using render.yaml (Recommended)

1. **Push your code** to GitHub
2. **Connect the repository** to Render
3. **Render will automatically detect** the `render.yaml` file
4. **Add your OpenAI API key** in the environment variables
5. **Deploy**

The `render.yaml` file is already configured for easy deployment.

## Testing

### Web Interface
- Visit `/test.html` for a user-friendly testing interface
- Supports drag-and-drop file upload
- Real-time preview of selected images
- Displays detailed results in a formatted view

### API Testing
```bash
# Test with curl
curl -X POST http://your-render-url/api/classify \
  -F "images[]=@path/to/car-image.jpg"

# Test with Postman
# Method: POST
# URL: http://your-render-url/api/classify
# Body: form-data
# Key: images[] (File)
```

## File Structure

```
car-classifier-dubicars-test-api/
├── public/
│   ├── index.php          # Main entry point
│   └── test.html          # Web test interface
├── src/
│   └── classify_handler.php # Main API logic
├── uploads/               # Temporary upload directory
├── vendor/                # Composer dependencies
├── composer.json          # PHP dependencies
├── render.yaml           # Render deployment config
├── .env                  # Environment variables (not in git)
├── env.example           # Environment template
├── .gitignore           # Git ignore rules
└── README.md            # This file
```

## Environment Variables

| Variable | Description | Required |
|----------|-------------|----------|
| `OPENAI_API_KEY` | Your OpenAI API key | Yes |

## Error Handling

The API includes comprehensive error handling for:
- Missing API keys
- Invalid file uploads
- Network errors
- OpenAI API errors
- Invalid JSON responses

## Security Considerations

- API key is stored in environment variables
- File uploads are validated for image types
- Temporary files are cleaned up after processing
- CORS headers are configured for web access

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the MIT License.

## Support

For issues and questions:
1. Check the error messages in the API response
2. Verify your OpenAI API key is valid
3. Ensure uploaded files are valid images
4. Check the Render deployment logs if deployed 