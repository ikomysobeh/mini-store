import{P as v,c as r,r as k,d as m}from"./index-D4dNSDEJ.js";import{d as c,c as l,a as p,o as u,b as d,z as y,A as h,u as n,L as x,M as S}from"./app-CdWJsdjD.js";var g=c({__name:"BaseSeparator",props:{orientation:{type:String,required:!1,default:"horizontal"},decorative:{type:Boolean,required:!1},asChild:{type:Boolean,required:!1},as:{type:null,required:!1}},setup(a){const e=a,t=["horizontal","vertical"];function s(o){return t.includes(o)}const i=l(()=>s(e.orientation)?e.orientation:"horizontal"),_=l(()=>i.value==="vertical"?e.orientation:void 0),f=l(()=>e.decorative?{role:"none"}:{"aria-orientation":_.value,role:"separator"});return(o,C)=>(u(),p(n(v),h({as:o.as,"as-child":o.asChild,"data-orientation":i.value},f.value),{default:d(()=>[y(o.$slots,"default")]),_:3},16,["as","as-child","data-orientation"]))}}),z=g,B=c({__name:"Separator",props:{orientation:{type:String,required:!1,default:"horizontal"},decorative:{type:Boolean,required:!1},asChild:{type:Boolean,required:!1},as:{type:null,required:!1}},setup(a){const e=a;return(t,s)=>(u(),p(z,x(S(e)),{default:d(()=>[y(t.$slots,"default")]),_:3},16))}}),q=B;/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const O=r("LogOutIcon",[["path",{d:"M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4",key:"1uf3rs"}],["polyline",{points:"16 17 21 12 16 7",key:"1gabdz"}],["line",{x1:"21",x2:"9",y1:"12",y2:"12",key:"1uyos4"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const b=r("MenuIcon",[["line",{x1:"4",x2:"20",y1:"12",y2:"12",key:"1e0a9i"}],["line",{x1:"4",x2:"20",y1:"6",y2:"6",key:"1owob3"}],["line",{x1:"4",x2:"20",y1:"18",y2:"18",key:"yk5zj1"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const P=r("ShoppingCartIcon",[["circle",{cx:"8",cy:"21",r:"1",key:"jimo8o"}],["circle",{cx:"19",cy:"21",r:"1",key:"13723u"}],["path",{d:"M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12",key:"9zh506"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const L=r("UserIcon",[["path",{d:"M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2",key:"975kel"}],["circle",{cx:"12",cy:"7",r:"4",key:"17ys0d"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const w=r("XIcon",[["path",{d:"M18 6 6 18",key:"1bl5f8"}],["path",{d:"m6 6 12 12",key:"d8bk6v"}]]),H=c({__name:"Separator",props:{orientation:{default:"horizontal"},decorative:{type:Boolean,default:!0},asChild:{type:Boolean},as:{},class:{}},setup(a){const e=a,t=k(e,"class");return(s,i)=>(u(),p(n(q),h({"data-slot":"separator-root"},n(t),{class:n(m)("bg-border shrink-0 data-[orientation=horizontal]:h-px data-[orientation=horizontal]:w-full data-[orientation=vertical]:h-full data-[orientation=vertical]:w-px",e.class)}),null,16,["class"]))}});export{O as L,b as M,P as S,L as U,w as X,H as _};
