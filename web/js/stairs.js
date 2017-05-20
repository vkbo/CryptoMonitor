/**
 *  Crypto Monitor – Stairs Plot Script
 * =====================================
 *  Created 2017-05-19
 */

function stairs(aAxis, aData, iWidth, iHeight, sCanvas) {

    var canvas = document.getElementById(sCanvas);
    var ctx = canvas.getContext("2d");
    ctx.translate(0, canvas.height);
    ctx.scale(1, -1);

    var xMargin = 50;
    var yMargin = 50;
    var xSize = iWidth - xMargin - 20;
    var ySize = iHeight - yMargin - 20;

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
    var xElemW = xPlot/(xElems-1);

    ctx.beginPath();
    ctx.strokeStyle="#ff0000";
    ctx.lineWidth=2;
    ctx.moveTo(xOrig,yOrig);
    for(i=0; i<aAxis.length; i++) {
        ctx.lineTo(xOrig+xElemW*i,    aData[i]*50+yOrig);
        ctx.lineTo(xOrig+xElemW*(i+1),aData[i]*50+yOrig);
    }
    ctx.lineTo(xOrig+xElemW*(i-1),yOrig);
    ctx.stroke();

}
