/**
 *  Crypto Monitor – Earnings Plot Script
 * =======================================
 *  Created 2017-05-21
 */

function plotEarnings(aAxis, aHash, aCoin, sCanvas) {

    var canvas = document.getElementById(sCanvas);
    var ctx = canvas.getContext("2d");
    ctx.translate(0, canvas.height);
    ctx.scale(1, -1);

    var cWidth  = canvas.width;
    var cHeight = canvas.height;

    var xMargin = 50;
    var yMargin = 50;
    var xSize = cWidth - xMargin - 20;
    var ySize = cHeight - yMargin - 20;

    ctx.moveTo(xMargin,yMargin);
    ctx.lineTo(xMargin,yMargin+ySize);
    ctx.stroke();

    ctx.moveTo(xMargin,yMargin);
    ctx.lineTo(xMargin+xSize,yMargin);
    ctx.stroke();

    var xOrig = xMargin+4;
    var yOrig = yMargin+1;
    var xPlot = xSize-8;
    var yPlot = ySize-1;

    var xElems = aAxis.length;
    var xElemW = xPlot/(xElems);

    refIdx = 0;
    for(i=0; i<aAxis.length; i++) {
        if(aCoin[i] == 0 || aHash[i] == 0) {
            refIdx++;
        } else {
            break;
        }
    }
    console.log(refIdx);

    ctx.beginPath();
    ctx.strokeStyle="#999999";
    ctx.lineWidth=2;
    ctx.moveTo(xOrig,yOrig);
    for(i=0; i<aAxis.length; i++) {
        ctx.lineTo(xOrig+xElemW*(i+1),aHash[i]/aHash[refIdx]*50+yOrig);
    }
    ctx.stroke();

    ctx.beginPath();
    ctx.strokeStyle="#ff0000";
    ctx.lineWidth=2;
    ctx.moveTo(xOrig,yOrig);
    for(i=0; i<aAxis.length; i++) {
        ctx.lineTo(xOrig+xElemW*(i+1),aCoin[i]/aCoin[refIdx]*50+yOrig);
    }
    ctx.stroke();

}
