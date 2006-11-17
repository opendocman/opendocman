//**************************************************************** 
// You are free to copy the "Folder-Tree" script as long as you  
// keep this copyright notice: 
// Script found in: http://www.geocities.com/marcelino_martins/foldertree.html
// Author: Marcelino Alves Martins (http://www.mmartins.com) 
// 1997--2001. 
// Modified: Patrick Hess (posi@posi.de) March 2000, October 2001^M
//           changed: global variables define the path to the^M
//                    .gif files (classPath), the names of the^M
//                    images (ftv2folderopen, ftv2*, ...) and the^M
//                    destination frame (basefrm)^M
//**************************************************************** 
 
// Log of changes: 
//       10 Aug 01 - Support for Netscape 6
//
//       17 Feb 98 - Fix initialization flashing problem with Netscape
//       
//       27 Jan 98 - Root folder starts open; support for USETEXTLINKS; 
//                   make the ftien4 a js file 
 
 
// Definition of class Folder 
// ***************************************************************** 
 
function Folder(folderDescription, hreference, framename, open, closed) //constructor 
{ 
  //constant data 
  this.imgopen = open;
  this.imgclosed = closed;
  this.desc = folderDescription 
  this.hreference = hreference 
  this.framename = framename
  this.id = -1   
  this.navObj = 0  
  this.iconImg = 0  
  this.nodeImg = 0  
  this.isLastNode = 0 
 
  //dynamic data 
  this.isOpen = true 
  this.iconSrc = classPath + "/" + this.imgopen          // classPath + "/" + ftv2folderopen  
  this.children = new Array 
  this.nChildren = 0 
 
  //methods 
  this.initialize = initializeFolder 
  this.setState = setStateFolder 
  this.addChild = addChild 
  this.createIndex = createEntryIndex 
  this.escondeBlock = escondeBlock
  this.esconde = escondeFolder 
  this.mostra = mostra 
  this.renderOb = drawFolder 
  this.totalHeight = totalHeight 
  this.subEntries = folderSubEntries 
  this.outputLink = outputFolderLink 
  this.blockStart = blockStart
  this.blockEnd = blockEnd
} 
 
function initializeFolder(level, lastNode, leftSide) 
{ 
  var j=0 
  var i=0 
  var numberOfFolders 
  var numberOfDocs 
  var nc 
      
  nc = this.nChildren 
   
  this.createIndex() 
 
  var auxEv = "" 
 
  if (browserVersion > 0) 
    auxEv = "<a href='javascript:clickOnNode("+this.id+")'>" 
  else 
    auxEv = "<a>" 
 
  if (level>0) 
    if (lastNode) //the last child in the children array 
    { 
      this.renderOb(leftSide + auxEv + "<img name='nodeIcon" + this.id + "' id='nodeIcon" + this.id + "' src='" + classPath + "/" + ftv2mlastnode + "' width=16 height=22 border=0></a>") 
      leftSide = leftSide + "<img src='" + classPath + "/" + ftv2blank + "' width=16 height=22>"  
      this.isLastNode = 1 
    } 
    else 
    { 
      this.renderOb(leftSide + auxEv + "<img name='nodeIcon" + this.id + "' id='nodeIcon" + this.id + "' src='" + classPath + "/" + ftv2mnode + "' width=16 height=22 border=0></a>") 
      leftSide = leftSide + "<img src='" + classPath + "/" + ftv2vertline + "' width=16 height=22>" 
      this.isLastNode = 0 
    } 
  else 
    this.renderOb("") 
   
  if (nc > 0) 
  { 
    level = level + 1 
    for (i=0 ; i < this.nChildren; i++)  
    { 
      if (i == this.nChildren-1) 
        this.children[i].initialize(level, 1, leftSide) 
      else 
        this.children[i].initialize(level, 0, leftSide) 
      } 
  } 
} 
 
function setStateFolder(isOpen) 
{ 
  var subEntries 
  var totalHeight 
  var fIt = 0 
  var i=0 
 
  if (isOpen == this.isOpen) 
    return 
 
  if (browserVersion == 2)  
  { 
    totalHeight = 0 
    for (i=0; i < this.nChildren; i++) 
      totalHeight = totalHeight + this.children[i].navObj.clip.height 
      subEntries = this.subEntries() 
    if (this.isOpen) 
      totalHeight = 0 - totalHeight 
    for (fIt = this.id + subEntries + 1; fIt < nEntries; fIt++) 
      indexOfEntries[fIt].navObj.moveBy(0, totalHeight) 
  }  
  this.isOpen = isOpen 
  propagateChangesInState(this) 
} 
 
function propagateChangesInState(folder) 
{   
  var i=0 
 
  if (folder.isOpen) 
  { 
    if (folder.nodeImg) 
      if (folder.isLastNode) 
        folder.nodeImg.src = classPath + "/"  + ftv2mlastnode 
      else 
	folder.nodeImg.src = classPath + "/" + ftv2mnode 
    folder.iconImg.src = classPath + "/" + folder.imgopen // classPath + "/" + ftv2folderopen 
    for (i=0; i<folder.nChildren; i++) 
      folder.children[i].mostra() 
  } 
  else 
  { 
    if (folder.nodeImg) 
      if (folder.isLastNode) 
        folder.nodeImg.src = classPath + "/" + ftv2plastnode 
      else 
	    folder.nodeImg.src = classPath + "/" + ftv2pnode 
    folder.iconImg.src = classPath + "/" + folder.imgclosed  // classPath + "/" + ftv2folderclosed  
    for (i=0; i<folder.nChildren; i++) 
      folder.children[i].esconde() 
  }  
} 
 
function escondeFolder() 
{ 
  this.escondeBlock()
   
  this.setState(0) 
} 
 
function drawFolder(leftSide) 
{ 

  var idParam = "id='folder" + this.id + "'"

  if (browserVersion == 2) { 
    if (!doc.yPos) 
      doc.yPos=20 
  } 

  this.blockStart("folder")

  doc.write("<tr><td>") 
  doc.write(leftSide) 
  this.outputLink() 
  doc.write("<img id='folderIcon" + this.id + "' name='folderIcon" + this.id + "' src='" + this.iconSrc+"' border=0></a>") 
  doc.write("</td><td valign=middle nowrap>") 
  if (USETEXTLINKS) 
  { 
    this.outputLink() 
    doc.write(this.desc + "</a>") 
  } 
  else 
    doc.write(this.desc) 
  doc.write("</td>")  

  this.blockEnd()
 
  if (browserVersion == 1) { 
    this.navObj = doc.all["folder"+this.id] 
    this.iconImg = doc.all["folderIcon"+this.id] 
    this.nodeImg = doc.all["nodeIcon"+this.id] 
  } else if (browserVersion == 2) { 
    this.navObj = doc.layers["folder"+this.id] 
    this.iconImg = this.navObj.document.images["folderIcon"+this.id] 
    this.nodeImg = this.navObj.document.images["nodeIcon"+this.id] 
    doc.yPos=doc.yPos+this.navObj.clip.height 
  } else if (browserVersion == 3) { 
    this.navObj = doc.getElementById("folder"+this.id)
    this.iconImg = doc.getElementById("folderIcon"+this.id) 
    this.nodeImg = doc.getElementById("nodeIcon"+this.id)
  } 
} 
 
function outputFolderLink() 
{ 
  if (this.hreference) 
  { 
    doc.write("<a href='" + this.hreference + "' TARGET=\""+this.framename+"\" ") 
    if (browserVersion > 0) 
      doc.write("onClick='javascript:clickOnFolder("+this.id+")'") 
    doc.write(">") 
  } 
  else 
    doc.write("<a>") 
//  doc.write("<a href='javascript:clickOnFolder("+this.id+")'>")   
} 
 
function addChild(childNode) 
{ 
  this.children[this.nChildren] = childNode 
  this.nChildren++ 
  return childNode 
} 
 
function folderSubEntries() 
{ 
  var i = 0 
  var se = this.nChildren 
 
  for (i=0; i < this.nChildren; i++){ 
    if (this.children[i].children) //is a folder 
      se = se + this.children[i].subEntries() 
  } 
 
  return se 
} 
 
 
// Definition of class Item (a document or link inside a Folder) 
// ************************************************************* 
 
function Item(itemDescription, itemLink, image) // Constructor 
{ 
  // constant data 
  this.docimage = image
  this.desc = itemDescription 
  this.link = itemLink 
  this.id = -1 //initialized in initalize() 
  this.navObj = 0 //initialized in render() 
  this.iconImg = 0 //initialized in render() 
  this.iconSrc = classPath + "/" + this.docimage    // ftv2doc 
 
  // methods 
  this.initialize = initializeItem 
  this.createIndex = createEntryIndex 
  this.esconde = escondeBlock
  this.mostra = mostra 
  this.renderOb = drawItem 
  this.totalHeight = totalHeight 
  this.blockStart = blockStart
  this.blockEnd = blockEnd
} 
 
function initializeItem(level, lastNode, leftSide) 
{  
  this.createIndex() 
 
  if (level>0) 
    if (lastNode) //the last 'brother' in the children array 
    { 
      this.renderOb(leftSide + "<img src='" + classPath + "/" + ftv2lastnode + "' width=16 height=22>") 
      leftSide = leftSide + "<img src='" + classPath + "/" + ftv2blank + "' width=16 height=22>"  
    } 
    else 
    { 
      this.renderOb(leftSide + "<img src='" + classPath + "/" + ftv2node + "' width=16 height=22>") 
      leftSide = leftSide + "<img src='" + classPath + "/" + ftv2vertline + "' width=16 height=22>" 
    } 
  else 
    this.renderOb("")   
} 
 
function drawItem(leftSide) 
{ 
  this.blockStart("item")

  doc.write("<tr><td>") 
  doc.write(leftSide) 
  doc.write("<a href=" + this.link + ">") 
  doc.write("<img id='itemIcon"+this.id+"' ") 
  doc.write("src='"+this.iconSrc+"' border=0>") 
  doc.write("</a>") 
  doc.write("</td><td valign=middle nowrap>") 
  if (USETEXTLINKS) 
    doc.write("<a href=" + this.link + ">" + this.desc + "</a>") 
  else 
    doc.write(this.desc) 

  this.blockEnd()
 
  if (browserVersion == 1) { 
    this.navObj = doc.all["item"+this.id] 
    this.iconImg = doc.all["itemIcon"+this.id] 
  } else if (browserVersion == 2) { 
    this.navObj = doc.layers["item"+this.id] 
    this.iconImg = this.navObj.document.images["itemIcon"+this.id] 
    doc.yPos=doc.yPos+this.navObj.clip.height 
  } else if (browserVersion == 3) { 
    this.navObj = doc.getElementById("item"+this.id)
    this.iconImg = doc.getElementById("itemIcon"+this.id)
  } 
} 
 
 
// Methods common to both objects (pseudo-inheritance) 
// ******************************************************** 
 
function mostra() 
{ 
  if (browserVersion == 1 || browserVersion == 3) 
    this.navObj.style.display = "block" 
  else 
    this.navObj.visibility = "show" 
} 

function escondeBlock() 
{ 
  if (browserVersion == 1 || browserVersion == 3) { 
    if (this.navObj.style.display == "none") 
      return 
    this.navObj.style.display = "none" 
  } else { 
    if (this.navObj.visibility == "hidden") 
      return 
    this.navObj.visibility = "hidden" 
  }     
} 
 
function blockStart(idprefix) {
  var idParam = "id='" + idprefix + this.id + "'"

  if (browserVersion == 2) 
    doc.write("<layer "+ idParam + " top=" + doc.yPos + " visibility=show>") 
     
  if (browserVersion == 3) //N6 has bug on display property with tables
    doc.write("<div " + idParam + " style='display:block; position:block;'>")
     
  doc.write("<table border=0 cellspacing=0 cellpadding=0 ") 

  if (browserVersion == 1) 
    doc.write(idParam + " style='display:block; position:block; '>") 
  else
    doc.write(">") 
}

function blockEnd() {
  doc.write("</table>") 
   
  if (browserVersion == 2) 
    doc.write("</layer>") 
  if (browserVersion == 3) 
    doc.write("</div>") 
}
 
function createEntryIndex() 
{ 
  this.id = nEntries 
  indexOfEntries[nEntries] = this 
  nEntries++ 
} 
 
// total height of subEntries open 
function totalHeight() //used with browserVersion == 2 
{ 
  var h = this.navObj.clip.height 
  var i = 0 
   
  if (this.isOpen) //is a folder and _is_ open 
    for (i=0 ; i < this.nChildren; i++)  
      h = h + this.children[i].totalHeight() 
 
  return h 
} 

 
// Events 
// ********************************************************* 
 
function clickOnFolder(folderId) 
{ 
  var clicked = indexOfEntries[folderId] 
 
  if (!clicked.isOpen) 
    clickOnNode(folderId) 
 
  return  
 
  if (clicked.isSelected) 
    return 
} 
 
function clickOnNode(folderId) 
{ 
  var clickedFolder = 0 
  var state = 0 
 
  clickedFolder = indexOfEntries[folderId] 
  state = clickedFolder.isOpen 
 
  clickedFolder.setState(!state) //open<->close  
} 
 

// Auxiliary Functions for Folder-Tree backward compatibility 
// *********************************************************** 
 
function gFld(description, hreference, framename, open, closed) 
{ 
  folder = new Folder(description, hreference, framename, open, closed) 
  return folder 
} 
 
function gLnk(target, description, linkData, framename, image) 
{ 
  fullLink = "" 
 
  if (target==0) 
  { 
    fullLink = "'"+linkData+"' target=\""+framename+"\"" 
  } 
  else 
  { 
    if (target==1) 
       // fullLink = "'http://"+linkData+"' target=_blank" 
       fullLink = "'"+linkData+"' target=_blank" 
    else 
       // fullLink = "'http://"+linkData+"' target=\""+framename+"\"" 
       fullLink = "'"+linkData+"' target=\""+framename+"\"" 
  } 
 
  linkItem = new Item(description, fullLink, image)   
  return linkItem 
} 
 
function insFld(parentFolder, childFolder) 
{ 
  return parentFolder.addChild(childFolder) 
} 
 
function insDoc(parentFolder, document) 
{ 
  parentFolder.addChild(document) 
} 
 

// Global variables 
// **************** 
 
//These two variables are overwriten on defineMyTree.js if needed be
//USETEXTLINKS = 0 
//STARTALLOPEN = 0
indexOfEntries = new Array 
nEntries = 0 
doc = document 
browserVersion = 0 
selectedFolder=0


// Main function
// ************* 

// This function uses an object (navigator) defined in
// ua.js, imported in the main html page (left frame).
function initializeDocument() 
{ 
  switch(navigator.family)
  {
    case 'opera':
    case 'ie4':
      browserVersion = 1 //IE4   
      break;
    case 'nn4':
      browserVersion = 2 //NS4 
      break;
    case 'gecko':
      browserVersion = 3 //NS6
      break;
	default:
	  browserVersion = 0 //other 
	  break;
  }      

  //foldersTree (with the site's data) is created in an external .js 
  foldersTree.initialize(0, 1, "") 
  
  if (browserVersion == 2) 
    doc.write("<layer top="+indexOfEntries[nEntries-1].navObj.top+">&nbsp;</layer>") 

  //The tree starts in full display 
  if (!STARTALLOPEN)
	  if (browserVersion > 0) {
		// close the whole tree 
		clickOnNode(0) 
		// open the root folder 
		clickOnNode(0) 
	  } 

  /* not used, ph 10/2001
  if (browserVersion == 0) 
	doc.write("<table border=0><tr><td><br><br><font size=-1>This tree only expands or contracts with DHTML capable browsers</font></table>")
  */
} 
 
