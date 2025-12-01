#!/bin/bash
echo "========== TESTING LOGIN API =========="
echo ""
echo "1. Testing DTEHM ID: DTEHM20259018"
curl -s -X POST http://localhost:8888/dtehm-insurance-api/public/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"DTEHM20259018","password":"123456"}' | jq -r '.code, .message'
echo ""

echo "2. Testing DIP ID: DIP0001"
curl -s -X POST http://localhost:8888/dtehm-insurance-api/public/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"DIP0001","password":"123456"}' | jq -r '.code, .message'
echo ""

echo "3. Testing Phone: +256706638484"
curl -s -X POST http://localhost:8888/dtehm-insurance-api/public/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"+256706638484","password":"123456"}' | jq -r '.code, .message'
echo ""

echo "4. Testing Phone without country code: 0706638484"
curl -s -X POST http://localhost:8888/dtehm-insurance-api/public/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"0706638484","password":"123456"}' | jq -r '.code, .message'
echo ""

echo "5. Testing Email: pefunuh@mailinator.com"
curl -s -X POST http://localhost:8888/dtehm-insurance-api/public/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"pefunuh@mailinator.com","password":"123456"}' | jq -r '.code, .message'
echo ""
echo "========== TESTS COMPLETE =========="
