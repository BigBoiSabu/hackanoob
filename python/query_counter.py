import sys

def add(total):

	queryToAdd = total

	print("adding "+str(total))

	f = open('/home/elite/hackanoob/status/queries.txt', 'r+')

	totalQuery = f.read()

	newTotal = int(totalQuery) + int(queryToAdd)

	f.seek(0)
	f.write(str(newTotal))
	f.truncate()


add(sys.argv[1])