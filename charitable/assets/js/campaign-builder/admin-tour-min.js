"use strict";var CharitableTour=window.CharitableTour||function(t,a,$){var e={init:function(){$(e.ready),$(a).on("load",(function(){"function"==typeof $.ready.then?$.ready.then(e.load):e.load()}))},ready:function(){},load:function(){e.setup()},setup:function(){"undefined"!=typeof Shepherd&&"undefined"!=typeof charitable_admin_builder_onboarding&&void 0!==charitable_admin_builder_onboarding.option&&void 0!==charitable_admin_builder_onboarding.option.tour&&void 0!==charitable_admin_builder_onboarding.option.tour.status&&("init"===charitable_admin_builder_onboarding.option.tour.status||""===charitable_admin_builder_onboarding.option.tour.status?(e.events(),e.setupTour()):charitable_admin_builder_onboarding.option.tour.status)},setupTour:function(){const a=new Shepherd.Tour({defaultStepOptions:{cancelIcon:{enabled:!0},exitOnEsc:!0,classes:"",classPrefix:"wpchar",scrollTo:{behavior:"smooth",block:"center"},when:{show(){const t=$(".shepherd-footer"),e=a?.getCurrentStep(),i=a?.steps.indexOf(e)+1,r=a?.steps.length;if(1!==i&&i!==r){var n=i/r*100;n>100&&(n=100),t.after('<span class="chartiable-tour-progress-bar"><span class="charitable-tour-progress" style="width: '+n+'%"></span></span>'),i>2&&$(".shepherd-target").removeClass("shepherd-target")}}}},tourName:"wpchar-visual-campaign-builder",useModalOverlay:!0,modalContainer:t.getElementById("charitable-builder"),stepContainer:t.getElementById("charitable-builder")});a.addStep({id:"wpchar-visual-campaign-builder-step-0",text:charitable_builder.onboarding_tour.step_0_text,arrow:!1,cancelIcon:{enabled:!0},classes:"wpchar-visual-campaign-builder-step-0",buttons:[{text:charitable_builder.onboarding_tour.start_tour,classes:"charitable-tour-btn-primary",action:a.next}]});a.addStep({id:"wpchar-visual-campaign-builder-step-1",title:charitable_builder.onboarding_tour.step_1_title,text:charitable_builder.onboarding_tour.step_1_text,attachTo:{element:"#charitable_settings_title",on:"bottom"},classes:"wpchar-visual-campaign-builder-step-1",buttons:[{text:charitable_builder.onboarding_tour.next,classes:"charitable-tour-btn-primary charitable-tour-btn-disabled",action:a.next}]});a.addStep({id:"wpchar-visual-campaign-builder-step-2",title:charitable_builder.onboarding_tour.step_2_title,text:charitable_builder.onboarding_tour.step_2_text,arrow:!0,modalOverlayOpeningPadding:8,attachTo:{element:".charitable-template-animal-sanctuary div.charitable-template",on:"right"},classes:"wpchar-visual-campaign-builder-step-2",buttons:[{text:charitable_builder.onboarding_tour.choose_a_template,classes:"charitable-tour-btn-primary",action:a.hide}]}),a.addStep({id:"wpchar-visual-campaign-builder-step-3",title:charitable_builder.onboarding_tour.step_3_title,text:charitable_builder.onboarding_tour.step_3_text,arrow:!0,modalOverlayOpeningPadding:5,attachTo:{element:"#add-layout",on:"right-end"},classes:"wpchar-visual-campaign-builder-step-3",buttons:[{text:charitable_builder.onboarding_tour.next,classes:"charitable-tour-btn-primary",action:a.next}]}),a.addStep({id:"wpchar-visual-campaign-builder-step-4",title:charitable_builder.onboarding_tour.step_4_title,text:charitable_builder.onboarding_tour.step_4_text,arrow:!0,modalOverlayOpeningPadding:5,attachTo:{element:"#charitable-tour-block-1",on:"left"},classes:"wpchar-visual-campaign-builder-step-4",buttons:[{text:charitable_builder.onboarding_tour.next,classes:"charitable-tour-btn-primary",action:a.next}]}),a.addStep({id:"wpchar-visual-campaign-builder-step-5",title:charitable_builder.onboarding_tour.step_5_title,text:charitable_builder.onboarding_tour.step_5_text,arrow:!0,modalOverlayOpeningPadding:5,attachTo:{element:"#charitable-tour-block-2",on:"left"},classes:"wpchar-visual-campaign-builder-step-5",buttons:[{text:charitable_builder.onboarding_tour.next,classes:"charitable-tour-btn-primary",action:a.next}]}),a.addStep({id:"wpchar-visual-campaign-builder-step-6",title:charitable_builder.onboarding_tour.step_6_title,text:charitable_builder.onboarding_tour.step_6_text,arrow:!0,modalOverlayOpeningPadding:5,attachTo:{element:"#charitable-tour-block-3",on:"left"},classes:"wpchar-visual-campaign-builder-step-6",buttons:[{text:charitable_builder.onboarding_tour.next,classes:"charitable-tour-btn-primary",action:a.next}]}),a.addStep({id:"wpchar-visual-campaign-builder-step-7",title:charitable_builder.onboarding_tour.step_7_title,text:charitable_builder.onboarding_tour.step_7_text,arrow:!0,modalOverlayOpeningPadding:5,attachTo:{element:"#charitable-save",on:"top-end"},classes:"wpchar-visual-campaign-builder-step-7",buttons:[{text:charitable_builder.onboarding_tour.next,classes:"charitable-tour-btn-primary",action:function(){e.gotoDraftPublishStep(),a.next()}}]}),a.addStep({id:"wpchar-visual-campaign-builder-step-8",title:charitable_builder.onboarding_tour.step_8_title,text:charitable_builder.onboarding_tour.step_8_text,arrow:!0,modalOverlayOpeningPadding:10,attachTo:{element:"#charitable-tour-block-4",on:"left-start"},classes:"wpchar-visual-campaign-builder-step-8",buttons:[{text:charitable_builder.onboarding_tour.next,classes:"charitable-tour-btn-primary",action:function(){e.undoDraftPublishStep(),a.next()}}]}),a.addStep({id:"wpchar-visual-campaign-builder-step-9",title:charitable_builder.onboarding_tour.step_9_title,text:charitable_builder.onboarding_tour.step_9_text,arrow:!0,modalOverlayOpeningPadding:3,attachTo:{element:"#charitable-preview-btn",on:"top"},classes:"wpchar-visual-campaign-builder-step-9",buttons:[{text:charitable_builder.onboarding_tour.next,classes:"charitable-tour-btn-primary",action:function(){a.next()}}]}),a.addStep({id:"wpchar-visual-campaign-builder-step-10",title:charitable_builder.onboarding_tour.step_10_title,text:charitable_builder.onboarding_tour.step_10_text,arrow:!0,modalOverlayOpeningPadding:3,attachTo:{element:"#charitable-view-btn",on:"top"},classes:"wpchar-visual-campaign-builder-step-10",buttons:[{text:charitable_builder.onboarding_tour.next,classes:"charitable-tour-btn-primary",action:function(){a.next()}}]}),a.addStep({id:"wpchar-visual-campaign-builder-step-11",title:charitable_builder.onboarding_tour.step_11_title,text:charitable_builder.onboarding_tour.step_11_text,arrow:!0,modalOverlayOpeningPadding:3,attachTo:{element:"#charitable-embed",on:"top"},classes:"wpchar-visual-campaign-builder-step-11",buttons:[{text:charitable_builder.onboarding_tour.next,classes:"charitable-tour-btn-primary",action:function(){e.gotoSettingsStep(),a.next()}}]}),a.addStep({id:"wpchar-visual-campaign-builder-step-12",title:charitable_builder.onboarding_tour.step_12_title,text:charitable_builder.onboarding_tour.step_12_text,arrow:!0,modalOverlayOpeningPadding:0,attachTo:{element:"#charitable-panel-settings .charitable-panel-sidebar",on:"right"},classes:"wpchar-visual-campaign-builder-step-12",buttons:[{text:charitable_builder.onboarding_tour.next,classes:"charitable-tour-btn-primary",action:function(){CharitableCampaignBuilder.panelSwitch("design"),a.next()}}]}),a.addStep({id:"wpchar-visual-campaign-builder-step-13",title:charitable_builder.onboarding_tour.step_13_title,text:charitable_builder.onboarding_tour.step_13_text,arrow:!1,classes:"wpchar-visual-campaign-builder-step-13",cancelIcon:{enabled:!1},buttons:[{text:charitable_builder.onboarding_tour.lets_get_started,classes:"charitable-tour-btn-primary",action:a.next}]}),a.start()},events:function(){$("a.create-campaign").on("blur",(function(){$(t).trigger("select-template")})),$(t).on("charitableEditorScreenStart",(function(){setTimeout((function(){Shepherd.activeTour.next()}),500)})),$(t).on("enter-campaign-name",(()=>{name_step.isOpen()&&$(".charitable-tour-btn-primary").removeClass("charitable-tour-btn-disabled")})),$("#charitable_settings_title").on("input",(function(){$(this).val().length>=5?$(".charitable-tour-btn-primary").removeClass("charitable-tour-btn-disabled"):$(".charitable-tour-btn-primary").addClass("charitable-tour-btn-disabled")})),["complete","cancel"].forEach((t=>Shepherd.on(t,(()=>{$(".charitable-tour-block").remove();var a={action:"charitable_onboarding_tour_save_option",dataType:"json",data:{type:"tour",optionData:{status:"complete"===t?"completed":"skipped"}},nonce:charitable_admin_builder_onboarding.nonce};$.post(charitable_builder.ajax_url,a,(function(t){t.success})).fail((function(t,a,e){})).always((function(){}))}))))},openVideo:function(){$("#charitable-tour-video").html('<iframe width="560" height="315" src="https://www.youtube.com/embed/834h3huzzk8?autoplay=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>')},gotoDraftPublishStep:function(t){var a=$("#charitable-status-button");a.parent().find("ul#charitable-status-dropdown").removeClass("charitable-hidden"),a.addClass("active")},undoDraftPublishStep:function(t){var a=$("#charitable-status-button");a.parent().find("ul#charitable-status-dropdown").addClass("charitable-hidden"),a.removeClass("active")},gotoSettingsStep:function(t){CharitableCampaignBuilder.panelSwitch("settings")}};return e}(document,window,jQuery);CharitableTour.init();