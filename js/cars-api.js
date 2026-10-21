/* Public car listing: DOM APIs are used so API values are never injected as HTML. */
(() => {
  'use strict';
  const list = document.getElementById('cars-list');
  if (!list) return;
  const element = (tag, cls, text) => { const el=document.createElement(tag); if(cls) el.className=cls; if(text !== undefined) el.textContent=text; return el; };
  const feature = (icon, text, type) => { const item=element('div','feature-item '+type); item.append(element('i','zmdi '+icon),element('span','desc',text)); return item; };
  function card(car) {
    const col=element('div','col-md-6 col-lg-4'), item=element('div','rental_item'), wrap=element('div','wrap_img'), img=element('img','image');
    img.src=car.image; img.alt=car.name; img.loading='lazy'; wrap.append(img);
    const bottom=element('div','bottom'), btn=element('div','wrap_btn'), a=element('a','ireca_btn btn_tran dashed btn_white btn_price'), content=element('span','wrap_content'), amount=element('span','amount');
    amount.textContent=Number(car.price).toLocaleString(); const currency=element('span','woocommerce-Price-amount amount'); currency.append(element('span','woocommerce-Price-currencySymbol',' '+car.currency)); amount.append(currency);
    content.append(amount,element('span','time','/ Day'),element('span','text',car.name)); a.append(content); btn.append(a); bottom.append(btn); wrap.append(bottom);
    const body=element('div','content'); body.append(element('h3','title',car.name)); const rating=element('div','woocommerce-product-rating'), stars=element('div','star-rating'); stars.setAttribute('role','img'); stars.setAttribute('aria-label','Rated 5 out of 5'); for(let i=0;i<5;i++)stars.append(element('i','zmdi zmdi-star'));rating.append(stars);body.append(rating);
    const features=element('div','features'), container=element('div','container-fluid'), row=element('div','row'); row.append(feature('zmdi-calendar-note',car.model,'odd'),feature('zmdi-car',car.brand,'eve')); if(car.description)row.append(feature('zmdi-info-outline',car.description,'odd')); container.append(row);features.append(container);body.append(features);item.append(wrap,body);col.append(item);return col;
  }
  fetch('api/cars?limit=100',{headers:{Accept:'application/json'}}).then(r=>r.ok?r.json():Promise.reject()).then(result=>{if(!result.ok)throw Error(); list.replaceChildren(...result.data.map(card)); if(!result.data.length)list.append(element('p','col-12 text-center','No cars are currently available.'));}).catch(()=>{ /* Keep the original static cards visible as a resilient fallback. */ });
})();
