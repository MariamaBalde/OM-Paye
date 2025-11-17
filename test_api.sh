#!/bin/bash

echo "🧪 Test des endpoints API Orange Money"
echo "======================================"

BASE_URL="http://127.0.0.1:8001"

echo ""
echo "1️⃣ Test endpoint LOGIN"
echo "POST /api/auth/login"
curl -X POST $BASE_URL/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"telephone": "782917770"}' \
  -s | head -20

echo ""
echo ""
echo "2️⃣ Test documentation Swagger"
echo "GET /api/documentation"
curl -s $BASE_URL/api/documentation | head -10

echo ""
echo ""
echo "3️⃣ Test route list"
echo "GET /api"
curl -s $BASE_URL/api | grep -o '<title>[^<]*' | head -5

echo ""
echo ""
echo "✅ Tests terminés"