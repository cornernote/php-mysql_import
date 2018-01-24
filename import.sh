#!/usr/bin/env bash


echo ""
echo ""
echo "IMPORTING: $3"


echo ""
echo "  build download script"
echo "      $1 $2/$3 build-download $4/$2/$3/download.sh"
echo ""
$1 $2/$3 build-download $4/$2/$3/download.sh
chmod +x $4/$2/$3/download.sh

echo ""
echo "  run download script"
echo "      bash $4/$2/$3/download.sh"
echo ""
bash $4/$2/$3/download.sh


echo ""
echo "  build import script"
echo "      $1 $2/$3 build-import $4/$2/$3/import.sh"
echo ""
$1 $2/$3 build-import $4/$2/$3/import.sh
chmod +x $4/$2/$3/import.sh

echo ""
echo "  run import script"
echo "      bash $4/$2/$3/import.sh"
echo ""
bash $4/$2/$3/import.sh


echo ""
