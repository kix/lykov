Reply: @lykov start @anton @stepan @alexander

Direct w/@martin
Bot: 
Hey @martin, pick a task! Say `/vote <Task>` to start estimating.

Reply: 
/vote T1000

Direct w/@everyone
Bot: 
Now estimating *T1000*. Pick a score!

Reply:
/score 1

Bot w/all: 
Estimations have diverged. 
@martin: 1, @anton: 2, @alexander: 3
Why'd you pick a score lower (higher) than the average *1.75*?

Bot w/@martin:
Bot:
Say `/revote` once you're ready to revote, or say `/vote <Task>` to move forward and save the average.

Reply:
/revote

Direct w/@everyone
Bot: 
Now estimating *T1000*. Pick a score!

Reply:
/score 2

Estimations have diverged. 
@martin: 2, @anton: 2, @alexander: 3
Average is *2,25*

Bot w/@martin:
Bot:
Say `/revote` once you're ready, or say `/vote <Task>` to move forward and save the average.

Reply:
/vote T1001

GOTO L10

Bot w/@martin:
Reply:
/finish

Bot:
Ok, here are the results:
```
Task  | Average
T1000 | 2,25
```