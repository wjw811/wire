INSERT INTO b_gateway (id,name,serial,xy,addr,fid,upgrade,uid,status,created,updated) VALUES (1,'演示网关','gw0001',NULL,'演示地址',0,0,0,1,NOW(),NOW());  
INSERT INTO b_dev (id,name,serial,addr,feature,gid,proto,star,uid,status,created,updated) VALUES (1,'演示设备A','01','演示地址','[\" "f10\,\f11\,\f12\,\f13\]',1,1,1,0,1,NOW(),NOW());  
INSERT INTO b_dev (id,name,serial,addr,feature,gid,proto,star,uid,status,created,updated) VALUES (2,'演示设备B','02','演示地址','[\f16\,\f17\,\f18\,\f19\]',1,1,0,0,1,NOW(),NOW());  
INSERT INTO b_calc_day (id,day,snap,created,updated) VALUES (1,DATE_FORMAT(NOW(),'%Y%m%d'),'{\k0\:{\g\:[5,2]}}',NOW(),NOW());  
