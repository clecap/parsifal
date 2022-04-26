const fs   = require("fs");
const path = require("path");
const crypto = require('crypto');


var number = 0;
var dirNumber = 0;

const getAllFiles = function(dirPath, arrayOfFiles) {
  files        = fs.readdirSync(dirPath);
  arrayOfFiles = arrayOfFiles || [];
  files.forEach(function(file) {
  //  console.log ("Found " + number + "  " + file);
    number ++;
    if (fs.statSync(dirPath + "/" + file).isDirectory()) { 
      dirNumber++;
      console.log ("Directory: " + dirNumber + "  " + file);
      arrayOfFiles = getAllFiles(dirPath + "/" + file, arrayOfFiles);
    } else {
      arrayOfFiles.push(path.join(dirPath, "/", file));
    }
  })

  return arrayOfFiles
};



function show (fileName) {
  try {
  const fileBuffer = fs.readFileSync(fileName);
  
  const hashSum = crypto.createHash('sha256');
  hashSum.update(fileBuffer);
  const hex = hashSum.digest('hex');
  console.log("Hash : ", n , " of " , fileName, " has ", hex);}
catch (x) {console.log ("ERROR: ", x); return;}

}

var arr = getAllFiles ("/opt01/LIBRARY");

var n = 0;
arr.forEach ( ele => console.log (n++, ele));

n = 0;
arr.forEach ( ele => show(ele) );

