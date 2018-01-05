drop table if exists single;
create table single (
  id int not null auto_increment primary key,
  date date,
  time time,
  tablebrand enum ("Tornado","Bonzini"),
  win varchar(32),
  lose varchar(32),
  goals_win tinyint,
  goals_lose tinyint,
  deleted tinyint default 0);

