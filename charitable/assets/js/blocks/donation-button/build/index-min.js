(()=>{"use strict";var e,t={"./src/edit.js":
/*!*********************!*\
  !*** ./src/edit.js ***!
  \*********************/(e,t,a)=>{a.r(t),a.d(t,{default:()=>i});var n=a(/*! react */"react"),o=a(/*! @wordpress/i18n */"@wordpress/i18n"),l=a(/*! @wordpress/components */"@wordpress/components"),r=a(/*! @wordpress/block-editor */"@wordpress/block-editor");charitable_block_data;function i({attributes:e,setAttributes:t}){const{campaignID:a,itemLabel:i,newTab:c,customCSS:s,buttonType:b}=e;return(0,n.useEffect)((()=>{null!=i&&""!==i||t({itemLabel:"Donate"})}),[i,t]),(0,n.useEffect)((()=>{null!=b&&""!==b||t({buttonType:"button"})}),[b,t]),(0,n.useEffect)((()=>{null!=c&&""!==c||t({showAmonewTabunt:1})}),[c,t]),(0,n.createElement)(n.Fragment,null,(0,n.createElement)(r.InspectorControls,null,(0,n.createElement)(l.PanelBody,{title:(0,o.__)("Settings","charitable")},(0,n.createElement)(l.SelectControl,{label:(0,o.__)("Campaign","charitable"),help:(0,o.__)("REQUIRED. This is the campaign the donation button is associated with.","charitable"),value:a,options:charitable_block_data.campaigns,onChange:e=>t({campaignID:e})}),(0,n.createElement)(l.TextControl,{label:(0,o.__)("Label","charitable"),help:(0,o.__)("Text visible on the button/link.","charitable"),value:i,onChange:e=>t({itemLabel:e})}),(0,n.createElement)(l.ToggleControl,{checked:c,label:(0,o.__)("New Tab","charitable"),help:(0,o.__)("Open the donation page in a new tab when clicked.","charitable"),onChange:()=>t({newTab:!c})}),(0,n.createElement)(l.TextControl,{label:(0,o.__)("Custom CSS","charitable"),help:(0,o.__)("Add custom CSS to style the button/link.","charitable"),value:s,onChange:e=>t({customCSS:e})}),(0,n.createElement)(l.SelectControl,{label:(0,o.__)("Button Type","charitable"),value:b,options:[{value:"button",label:"Button"},{value:"link",label:"Link"}],onChange:e=>t({buttonType:e})}),(0,n.createElement)("p",{className:"wpchar-gutenberg-panel-notice"},(0,n.createElement)("strong",null,charitable_block_data.panel_donation_button_notice_head),charitable_block_data.panel_donation_button_notice_text,(0,n.createElement)("a",{href:charitable_block_data.panel_donation_button_notice_link,rel:"noreferrer",target:"_blank"},charitable_block_data.panel_donation_button_notice_link_text)))),(0,n.createElement)("div",{...(0,r.useBlockProps)()},(0,n.createElement)("div",{className:"charitable-block charitable-logo"},(0,n.createElement)("img",{src:charitable_block_data.logo})),(0,n.createElement)("h5",null,(0,o.__)("Donation Button/Link Block","charitable")),(0,n.createElement)("p",null,(0,o.__)("Preview Not Available In Editor","charitable"))))}},"./src/index.js":
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/(e,t,a)=>{a.r(t);var n=a(/*! @wordpress/blocks */"@wordpress/blocks"),o=(a(/*! ./style.scss */"./src/style.scss"),a(/*! ./edit */"./src/edit.js")),l=(a(/*! ./save */"./src/save.js"),a(/*! ./block.json */"./src/block.json"));const r=wp.element.createElement,i=r("svg",{width:50,height:50,viewBox:"0 0 50 50",fill:"none",xmlns:"http://www.w3.org/2000/svg"},r("path",{d:"M50.0541 25C50.0541 38.7987 38.8528 50 25.0541 50C11.2554 50 0 38.7987 0 25C0 11.2013 11.2013 0 25 0C38.7987 0 50.0541 11.2013 50.0541 25Z",fill:"#F99E36"}),r("path",{d:"M34.8483 11.9048C32.9544 9.79442 31.0604 8.98273 29.4912 9.52386C29.1665 9.63208 28.8959 9.79442 28.6795 10.0109L24.0799 13.8529L12.1211 23.9178L9.52365 26.0823C8.65785 26.7858 8.33317 28.0304 8.71196 29.0585L9.19897 30.4654L9.68599 31.8723C10.0648 32.9546 11.0929 33.6581 12.2293 33.6581H37.2293C37.554 33.6581 38.2033 33.4957 38.2033 33.4957C39.7726 32.9005 40.6925 31.0607 40.8007 28.2468C40.9089 25.7035 40.3137 22.6191 39.1773 19.5888C38.0951 16.5044 36.5258 13.7987 34.8483 11.9048ZM34.0908 24.2966L32.2509 19.3182C33.6037 18.8312 35.173 19.5347 35.66 20.8875C36.2011 22.2944 35.4436 23.8096 34.0908 24.2966ZM29.2747 10.7143C29.2206 10.7684 29.2206 10.7684 29.1665 10.8226C27.2726 12.3918 27.3267 17.6407 29.3288 23.2143C30.7899 27.1646 32.8462 30.3031 34.8483 31.8182H12.2834C11.9587 31.8182 11.634 31.6018 11.5258 31.2771L11.0388 29.8702L27.8678 23.7555C25.7033 17.9654 26.4068 12.3377 29.2747 10.7143Z",fill:"white"}),r("path",{d:"M30.0322 33.4957C29.8699 36.0931 27.7054 37.9329 25.0539 37.987H24.9998C22.3482 37.987 20.1296 36.1472 19.9673 33.4957H21.8612C22.0777 35.0649 23.3764 36.1472 24.9998 36.1472H25.0539C26.6231 36.1472 27.9218 35.0108 28.1383 33.4957",fill:"white"}));(0,n.registerBlockType)(l.name,{icon:i,edit:o.default,save:()=>null})},"./src/save.js":
/*!*********************!*\
  !*** ./src/save.js ***!
  \*********************/(e,t,a)=>{a.r(t),a.d(t,{default:()=>l});var n=a(/*! react */"react"),o=a(/*! @wordpress/block-editor */"@wordpress/block-editor");function l({attributes:e}){const{fallbackCurrentYear:t,showStartingYear:a,startingYear:l}=e;if(!t)return null;let r;return r=a&&l?l+"–"+t:t,(0,n.createElement)("p",{...o.useBlockProps.save()},"© ",r)}},"./src/style.scss":
/*!************************!*\
  !*** ./src/style.scss ***!
  \************************/(e,t,a)=>{a.r(t)},react:
/*!************************!*\
  !*** external "React" ***!
  \************************/e=>{e.exports=window.React},"@wordpress/block-editor":
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/e=>{e.exports=window.wp.blockEditor},"@wordpress/blocks":
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/e=>{e.exports=window.wp.blocks},"@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/e=>{e.exports=window.wp.components},"@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/e=>{e.exports=window.wp.i18n},"./src/block.json":
/*!************************!*\
  !*** ./src/block.json ***!
  \************************/e=>{e.exports=JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"charitable/donation-button","version":"0.1.0","title":"Donation Button/Link","category":"widgets","icon":"feedback","description":"Display a donation button for a particular campaign.","keywords":["charitable","campaign","donation"],"attributes":{"campaignID":{"type":"string"},"customCSS":{"type":"string"},"itemLabel":{"type":"string"},"newTab":{"type":"boolean"},"buttonType":{"type":"string"}},"supports":{"html":false,"align":["center","left","right","wide"]},"textdomain":"charitable","editorScript":"file:./index.js","editorStyle":"file:./index.css","style":"file:./style-index.css","render":"file:./render.php"}')}},a={};function n(e){var o=a[e];if(void 0!==o)return o.exports;var l=a[e]={exports:{}};return t[e](l,l.exports,n),l.exports}n.m=t,e=[],n.O=(t,a,o,l)=>{if(!a){var r=1/0;for(b=0;b<e.length;b++){a=e[b][0],o=e[b][1],l=e[b][2];for(var i=!0,c=0;c<a.length;c++)(!1&l||r>=l)&&Object.keys(n.O).every((e=>n.O[e](a[c])))?a.splice(c--,1):(i=!1,l<r&&(r=l));if(i){e.splice(b--,1);var s=o();void 0!==s&&(t=s)}}return t}l=l||0;for(var b=e.length;b>0&&e[b-1][2]>l;b--)e[b]=e[b-1];e[b]=[a,o,l]},n.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return n.d(t,{a:t}),t},n.d=(exports,e)=>{for(var t in e)n.o(e,t)&&!n.o(exports,t)&&Object.defineProperty(exports,t,{enumerable:!0,get:e[t]})},n.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),n.r=exports=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(exports,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(exports,"__esModule",{value:!0})},(()=>{var e={index:0,"./style-index":0};n.O.j=t=>0===e[t];var t=(t,a)=>{var o,l,r=a[0],i=a[1],c=a[2],s=0;if(r.some((t=>0!==e[t]))){for(o in i)n.o(i,o)&&(n.m[o]=i[o]);if(c)var b=c(n)}for(t&&t(a);s<r.length;s++)l=r[s],n.o(e,l)&&e[l]&&e[l][0](),e[l]=0;return n.O(b)},a=self.webpackChunk_wp_block_development_examples_copyright_date_block_09aac3=self.webpackChunk_wp_block_development_examples_copyright_date_block_09aac3||[];a.forEach(t.bind(null,0)),a.push=t.bind(null,a.push.bind(a))})();var o=n.O(void 0,["./style-index"],(()=>n("./src/index.js")));o=n.O(o)})();