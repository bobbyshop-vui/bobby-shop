import{i as v,m as H,u as z,l as F}from"./index.20192476.js";import{u as I}from"./vue3-apexcharts.8b5abfad.js";import{C as V}from"./Blur.31bfcf06.js";import{C as M}from"./Card.46af096e.js";import{G as O}from"./Graph.266090de.js";import{G as E,a as G}from"./Row.f01f32cd.js";import{P as U}from"./PostsTable.ef726bf7.js";import{S as N}from"./SeoStatisticsOverview.39008fc7.js";import"./translations.12335a6a.js";import{_ as D}from"./_plugin-vue_export-helper.249dac1d.js";import{_ as c}from"./default-i18n.54b5d8cd.js";import{v as r,o as d,k as b,l as o,a as L,C as a,x as y,t as w,c as B,u as s,b as x}from"./runtime-dom.esm-bundler.6789c400.js";import{C as q}from"./Index.c0a0a208.js";import{R as A}from"./RequiredPlans.7629fd28.js";import{u as W}from"./License.f957e227.js";import"./helpers.f95d5840.js";import"./Tooltip.b6b45c85.js";import"./Caret.662da1f3.js";import"./index.ee8124c6.js";import"./Slide.d0517fb0.js";import"./numbers.b55b32c5.js";import"./PostTypes.d6c1987b.js";import"./Statistic.3ce52bbb.js";import"./_baseClone.e959332d.js";import"./_arrayEach.f4f00336.js";import"./_getTag.8dc22eac.js";import"./vue-router.fc4966b9.js";import"./datetime.cb3980ce.js";import"./WpTable.46584896.js";import"./ScrollTo.97c9805f.js";import"./license.37048367.js";import"./upperFirst.96c04516.js";import"./_stringToArray.08127ca9.js";import"./toString.1401d490.js";import"./ScoreButton.e2a31604.js";import"./Table.963c11c7.js";import"./Download.6a0d8455.js";import"./IndexStatus.d0fe7619.js";import"./CheckSolid.9e151b0e.js";import"./Calendar.4686ac3f.js";import"./Mobile.3fcac169.js";import"./Checkmark.32f79576.js";import"./ExclamationSolid.8e60a4a3.js";import"./Link.dc972d1e.js";import"./constants.2019bcb3.js";import"./addons.9d0af6ad.js";const m="all-in-one-seo-pack",j={setup(){return{searchStatisticsStore:v()}},components:{CoreBlur:V,CoreCard:M,Graph:O,GridColumn:E,GridRow:G,PostsTable:U,SeoStatisticsOverview:N},data(){return{strings:{seoStatisticsCard:c("SEO Statistics",m),seoStatisticsTooltip:c("The following SEO Statistics graphs are useful metrics for understanding the visibility of your website or pages in search results and can help you identify trends or changes over time.<br /><br />Note: This data is capped at the top 100 keywords per day to speed up processing and to help you prioritize your SEO efforts, so while the data may seem inconsistent with Google Search Console, this is intentional.",m),contentCard:c("Content",m),postsTooltip:c("These lists can be useful for understanding the performance of specific pages or posts and identifying opportunities for improvement. For example, the top winning content may be good candidates for further optimization or promotion, while the top losing may need to be reevaluated and potentially updated.",m)},defaultPages:{rows:[],totals:{page:0,pages:0,total:0}}}},computed:{series(){var n,e,l,i;return!((e=(n=this.searchStatisticsStore.data)==null?void 0:n.seoStatistics)!=null&&e.statistics)||!((i=(l=this.searchStatisticsStore.data)==null?void 0:l.seoStatistics)!=null&&i.intervals)?[]:[{name:c("Search Impressions",m),data:this.searchStatisticsStore.data.seoStatistics.intervals.map(t=>({x:t.date,y:t.impressions})),legend:{total:this.searchStatisticsStore.data.seoStatistics.statistics.impressions}},{name:c("Search Clicks",m),data:this.searchStatisticsStore.data.seoStatistics.intervals.map(t=>({x:t.date,y:t.clicks})),legend:{total:this.searchStatisticsStore.data.seoStatistics.statistics.clicks}}]}}},$={class:"aioseo-search-statistics-dashboard"},J=["innerHTML"];function K(n,e,l,i,t,S){const f=r("seo-statistics-overview"),C=r("graph"),g=r("core-card"),k=r("posts-table"),T=r("grid-column"),P=r("grid-row"),u=r("core-blur");return d(),b(u,null,{default:o(()=>[L("div",$,[a(P,null,{default:o(()=>[a(T,null,{default:o(()=>[a(g,{class:"aioseo-seo-statistics-card",slug:"seoPerformance","header-text":t.strings.seoStatisticsCard,toggles:!1,"no-slide":""},{tooltip:o(()=>[L("span",{innerHTML:t.strings.seoStatisticsTooltip},null,8,J)]),default:o(()=>[a(f,{statistics:["impressions","clicks","ctr","position"],"show-graph":!1,view:"side-by-side"}),a(C,{"multi-axis":"",series:S.series,"legend-style":"simple"},null,8,["series"])]),_:1},8,["header-text"]),a(g,{slug:"posts","header-text":t.strings.contentCard,toggles:!1,"no-slide":""},{tooltip:o(()=>[y(w(t.strings.postsTooltip),1)]),default:o(()=>{var p,_,R;return[a(k,{posts:((R=(_=(p=i.searchStatisticsStore.data)==null?void 0:p.seoStatistics)==null?void 0:_.pages)==null?void 0:R.paginated)||t.defaultPages,columns:["postTitle","indexStatus","seoScore","clicksSortable","impressionsSortable","positionSortable","diffPositionSortable"],"show-items-per-page":"","show-table-footer":""},null,8,["posts"])]}),_:1},8,["header-text"])]),_:1})]),_:1})])]),_:1})}const Q=D(j,[["render",K]]),X={class:"aioseo-search-statistics-seo-statistics"},Y={__name:"Index",setup(n){const{strings:e}=I(),l=H(),i=v(),t=z();return(S,f)=>(d(),B("div",X,[s(i).shouldShowSampleReports?x("",!0):(d(),b(s(Q),{key:0})),s(i).shouldShowSampleReports?x("",!0):(d(),b(s(q),{key:1,"cta-link":s(F).getPricingUrl("search-statistics","search-statistics-upsell","seo-statistics"),"button-text":s(e).ctaButtonText,"learn-more-link":s(F).getUpsellUrl("search-statistics","seo-statistics",s(t).isPro?"pricing":"liteUpgrade"),"cta-second-button-action":"",onCtaSecondButtonClick:s(i).showSampleReports,"second-button-text":s(e).ctaSecondButtonText,"cta-second-button-new-badge":"","cta-second-button-visible":"","feature-list":[s(e).feature1,s(e).feature2,s(e).feature3,s(e).feature4],"align-top":"","hide-bonus":!s(l).isUnlicensed},{"header-text":o(()=>[y(w(s(e).ctaHeader),1)]),description:o(()=>[a(s(A),{"core-feature":["search-statistics"]}),y(" "+w(s(e).ctaDescription),1)]),_:1},8,["cta-link","button-text","learn-more-link","onCtaSecondButtonClick","second-button-text","feature-list","hide-bonus"]))]))}},h="all-in-one-seo-pack",Z={setup(){return{searchStatisticsStore:v()}},components:{CoreCard:M,Graph:O,GridColumn:E,GridRow:G,PostsTable:U,SeoStatisticsOverview:N},data(){return{initialTableFilter:"",strings:{seoStatisticsCard:c("SEO Statistics",h),seoStatisticsTooltip:c("The following SEO Statistics graphs are useful metrics for understanding the visibility of your website or pages in search results and can help you identify trends or changes over time.<br /><br />Note: This data is capped at the top 100 keywords per day to speed up processing and to help you prioritize your SEO efforts, so while the data may seem inconsistent with Google Search Console, this is intentional.",h),contentCard:c("Content Performance",h),postsTooltip:c("These lists can be useful for understanding the performance of specific pages or posts and identifying opportunities for improvement. For example, the top winning content may be good candidates for further optimization or promotion, while the top losing may need to be reevaluated and potentially updated.",h)},defaultPages:{rows:[],totals:{page:0,pages:0,total:0}}}},computed:{series(){var n,e,l,i;return!((e=(n=this.searchStatisticsStore.data)==null?void 0:n.seoStatistics)!=null&&e.statistics)||!((i=(l=this.searchStatisticsStore.data)==null?void 0:l.seoStatistics)!=null&&i.intervals)?[]:[{name:c("Search Impressions",h),data:this.searchStatisticsStore.data.seoStatistics.intervals.map(t=>({x:t.date,y:t.impressions})),legend:{total:this.searchStatisticsStore.data.seoStatistics.statistics.impressions}},{name:c("Search Clicks",h),data:this.searchStatisticsStore.data.seoStatistics.intervals.map(t=>({x:t.date,y:t.clicks})),legend:{total:this.searchStatisticsStore.data.seoStatistics.statistics.clicks}}]}},beforeMount(){var n;if(Object.keys((n=this.$route)==null?void 0:n.query).includes("tab"))switch(this.$route.query.tab){case"TopLosingPages":this.initialTableFilter="topLosing";break;case"TopWinningPages":this.initialTableFilter="topWinning";break;default:this.initialTableFilter="all"}},mounted(){this.searchStatisticsStore.isConnected&&this.searchStatisticsStore.loadInitialData()}},tt={class:"aioseo-search-statistics-site-statistics"},st=["innerHTML"];function et(n,e,l,i,t,S){const f=r("seo-statistics-overview"),C=r("graph"),g=r("core-card"),k=r("posts-table"),T=r("grid-column"),P=r("grid-row");return d(),B("div",tt,[a(P,null,{default:o(()=>[a(T,null,{default:o(()=>[a(g,{class:"aioseo-seo-statistics-card",slug:"seoPerformance","header-text":t.strings.seoStatisticsCard,toggles:!1,"no-slide":""},{tooltip:o(()=>[L("span",{innerHTML:t.strings.seoStatisticsTooltip},null,8,st)]),default:o(()=>{var u,p;return[a(f,{statistics:["impressions","clicks","ctr","position"],"show-graph":!1,view:"side-by-side"}),a(C,{"multi-axis":"",series:S.series,loading:i.searchStatisticsStore.loading.seoStatistics,"legend-style":"simple",timelineMarkers:(p=(u=i.searchStatisticsStore.data)==null?void 0:u.seoStatistics)==null?void 0:p.timelineMarkers},null,8,["series","loading","timelineMarkers"])]}),_:1},8,["header-text"]),a(g,{slug:"posts","header-text":t.strings.contentCard,toggles:!1,"no-slide":""},{tooltip:o(()=>[y(w(t.strings.postsTooltip),1)]),default:o(()=>{var u,p,_;return[a(k,{posts:((_=(p=(u=i.searchStatisticsStore.data)==null?void 0:u.seoStatistics)==null?void 0:p.pages)==null?void 0:_.paginated)||t.defaultPages,columns:["postTitle","indexStatus","seoScore","clicksSortable","impressionsSortable","positionSortable"],"append-columns":{all:"diffPosition",topLosing:"diffDecay",topWinning:"diffDecay"},isLoading:i.searchStatisticsStore.loading.seoStatistics,initialFilter:t.initialTableFilter,"show-items-per-page":"","show-table-footer":""},null,8,["posts","isLoading","initialFilter"])]}),_:1},8,["header-text"])]),_:1})]),_:1})])}const it=D(Z,[["render",et]]),ot={class:"aioseo-search-statistics-seo-statistics"},Xt={__name:"SeoStatistics",setup(n){const e=v(),{shouldShowLite:l,shouldShowMain:i,shouldShowUpgrade:t}=W();return(S,f)=>(d(),B("div",ot,[s(i)("search-statistics","seo-statistics")||s(e).shouldShowSampleReports?(d(),b(s(it),{key:0})):x("",!0),(s(t)("search-statistics","seo-statistics")||s(l))&&!s(e).shouldShowSampleReports?(d(),b(s(Y),{key:1})):x("",!0)]))}};export{Xt as default};