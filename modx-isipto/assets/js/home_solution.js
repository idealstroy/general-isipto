'use strict';
// Animate the existing compact header without changing its shared CSS or URLs.
const headerContacts = document.getElementById('scrolling');
const headerMenu = document.getElementById('scrolling-menu');
const compactHeader = document.getElementById('scrolling-menu-top');
if (headerContacts && headerMenu && compactHeader) {
  const compactMenu = compactHeader.querySelector('.menu_box');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  let compactState = null;
  let animations = [];
  const syncHeader = () => {
    const compact = window.scrollY > 70 && window.innerWidth >= 992;
    if (compact === compactState) return;
    const animate = compactState !== null && window.innerWidth >= 992 && !reducedMotion.matches;
    compactState = compact;
    animations.forEach(animation => animation.cancel());
    animations = [];
    headerContacts.hidden = compact;
    headerMenu.hidden = compact;
    compactHeader.inert = !compact;
    if (!animate || !compactMenu) { compactHeader.hidden = !compact; return; }
    compactHeader.hidden = false;
    const options = {duration:200,easing:'ease-out'};
    const frames = [{opacity:0,transform:'translateY(-100%)'},{opacity:1,transform:'translateY(0)'}];
    const animation = compactMenu.animate(compact ? frames : frames.slice().reverse(), options);
    animation.onfinish = () => { compactHeader.hidden = !compactState; };
    animations.push(animation);
    if (!compact) [headerContacts,headerMenu].forEach(row=>animations.push(row.animate([{opacity:0},{opacity:1}],options)));
  };
  window.addEventListener('scroll',syncHeader,{passive:true});
  window.addEventListener('resize',syncHeader);
  window.addEventListener('pageshow',syncHeader);
  syncHeader();
}
